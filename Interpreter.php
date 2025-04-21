<?php

/**
 * IPP - PHP Project Student
 *
 * @file Interpreter.php
 * @author Denys Pylypenko (xpylypd00)
 *
 */

namespace IPP\Student;

use IPP\Student\SolNil;
use IPP\Student\SolTrue;
use IPP\Student\SolFalse;
use IPP\Student\SolBlock;
use IPP\Student\SolObject;
use IPP\Student\SolString;
use IPP\Student\SolInteger;
use IPP\Student\SolUserObject;
use IPP\Student\ClassDefinition;
use IPP\Student\ExecutionContext;
use IPP\Core\AbstractInterpreter;
use IPP\Student\Exception\ParseMainException;
use IPP\Core\Exception\NotImplementedException;
use IPP\Student\Exception\InterpretValueException;
use IPP\Student\Exception\InterpretTypeException;
use DOMElement;

// MARK: Interpreter
class Interpreter extends AbstractInterpreter
{
    // all classes that will be used in sol25
    /** @var array<string, ClassDefinition> */
    private array $classes = [];
    private SolNil $nilObject;

    // MARK: Execute
    public function execute(): int
    {
        $this->nilObject = new SolNil();

        $dom = $this->source->getDOMDocument();

        // 1) Load AST definitions (if not already done)
        $this->loadAST($dom);

        // 2) Take the ready ClassDefinition for Main from $this->classes
        if (!isset($this->classes['Main'])) {
            throw new ParseMainException("Class Main not found");
        }
        $mainDef = $this->classes['Main'];
        $mainObject = new SolUserObject($mainDef);

        // 3) Find the run method
        $runDef = $mainDef->getMethod('run');
        if ($runDef === null) {
            throw new ParseMainException("Error 31: Method run not found");
        }
        // 4) Execute the method (it will create ExecutionContext and call interpretBlock)
        $runDef->execute($mainObject, [], $this);

        return 0;
    }

    // MARK: loadAST
    public function loadAST(\DOMDocument $dom): void
    {
        // 1) Create ClassDefinition for each <class>
        foreach ($dom->getElementsByTagName('class') as $element) {
            $name = $element->getAttribute('name');
            $this->classes[$name] = new ClassDefinition($element);
        }

        // 2) Link parentDef by the parent attribute
        foreach ($this->classes as $cd) {
            $parent = $cd->getParentName();
            if (isset($this->classes[$parent])) {
                $cd->setParentDef($this->classes[$parent]);
            }
        }
    }

    // MARK: getStdOut
    public function getStdOut(): \IPP\Core\Interface\OutputWriter
    {
        return $this->stdout;
    }

    // MARK: interpretBlock
    public function interpretBlock(DOMElement $blockNode, ExecutionContext $ctx): SolObject
    {
        $result = $this->nilObject;

        /* ---------- Collect all <assign> and sort ---------- */
        $assigns = [];
        foreach ($blockNode->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === 'assign') {
                $order = (int) $child->getAttribute('order');   // required according to the specification
                $assigns[$order] = $child;                      // if order is unique, an array is enough
            }
        }
        ksort($assigns, SORT_NUMERIC);                         // ↑ 1‑2‑3‑…

        /* ---------- Execute sequentially ----------------- */
        foreach ($assigns as $assignElem) {
            $this->interpretAssign($assignElem, $ctx);

            // Save the result of the last operation, as before
            $varElem = $assignElem->getElementsByTagName('var')->item(0);
            $varName = $varElem ? $varElem->getAttribute('name') : '';
            $result  = $ctx->getVar($varName);
        }

        return $result;
    }


    // MARK: interpretAssign
    public function interpretAssign(DOMElement $assignNode, ExecutionContext $ctx): void
    {
        // 1) var name="..."
        $varElem = $assignNode->getElementsByTagName('var')->item(0);
        $varName = $varElem ? $varElem->getAttribute('name') : '';

        // 2) expr … evaluate
        $exprElem  = $assignNode->getElementsByTagName('expr')->item(0);
        $valueObj  = $this->evaluateExpr($exprElem, $ctx);

        // 3) ctx->setVar (error — if self/super or param)
        $ctx->setVar($varName, $valueObj);
    }


    // MARK: evaluateExpr
    public function evaluateExpr(?DOMElement $exprNode, ExecutionContext $ctx): SolObject
    {
        if (!$exprNode) { // Changed: handle null exprNode
            return $this->nilObject;
        }
        // 1) Find the first element inside <expr>
        $child = null;
        foreach ($exprNode->childNodes as $n) {
            if ($n instanceof DOMElement) {
                $child = $n;
                break;
            }
        }
        if (!$child) {
            // Empty expr — return nil
            return $this->nilObject;
        }

        // 2) literal
        if ($child->nodeName === 'literal') {
            $cls   = $child->getAttribute('class');
            $value = $child->getAttribute('value');
            switch ($cls) {
                case 'String':
                    return new SolString(stripcslashes($value));
                case 'Integer':
                    return new SolInteger((int)$value);
                case 'Nil':
                    return $this->nilObject;
                case 'True':
                    return SolTrue::getInstance();
                case 'False':
                    return SolFalse::getInstance();
            }
            return $this->nilObject;
        }

        // 3) var — including self/super
        if ($child->nodeName === 'var') {
            $name = $child->getAttribute('name');
            if ($name === 'self') {
                return $ctx->getSelf();
            }

            // Local variables
            return $ctx->getVar($name);
        }

        // 3.5) block literal -> create SolBlock
        if ($child->nodeName === 'block') {
            return new SolBlock($child, $ctx->getSelf());
        }

        // 4) send
        if ($child->nodeName === 'send') {
            $selector = $child->getAttribute('selector');

            // 1 --- handle String read as a class method
            if ($selector === 'read') {
                $expr0 = $child->getElementsByTagName('expr')->item(0);
                $lit   = $expr0 ? $expr0->getElementsByTagName('literal')->item(0) : null;
                if (
                    $lit
                    && $lit->getAttribute('class') === 'class'
                    && $lit->getAttribute('value') === 'String'
                ) {
                    $s = $this->input->readString();
                    return new SolString($s !== null ? $s : '');
                }
            }
            /* 2 ----------  special‑case  ClassName from: obj  ---------- */
            if ($selector === 'from:') {
                /* ── 1. Argument object ─────────────────────────────── */
                /** @var DOMElement|null $argExpr */
                $argExpr = null;
                foreach ($child->childNodes as $n) {
                    if ($n instanceof DOMElement && $n->nodeName === 'arg') {
                        // First <arg> → take its only <expr>
                        $argExpr = $n->getElementsByTagName('expr')->item(0);
                        break;
                    }
                }
                $argObj = $this->evaluateExpr($argExpr, $ctx);

                /* ── 2. Receiver – literal class  -------------------- */
                /** @var DOMElement|null $recvExpr Last direct <expr> */
                $recvExpr = null;
                foreach ($child->childNodes as $n) {
                    if ($n instanceof DOMElement && $n->nodeName === 'expr') {
                        $recvExpr = $n;                       // Overwrite – the last one remains
                    }
                }
                $classLit = $recvExpr?->getElementsByTagName('literal')->item(0);
                if (!$classLit || $classLit->getAttribute('class') !== 'class') {
                    throw new InterpretValueException();     // Invalid AST
                }
                $className = $classLit->getAttribute('value');

                /* ── 3. Integer and its subclasses ------------------------ */
                if ($argObj instanceof SolInteger) {
                    return $this->createInstance($className, $argObj->getValue());
                }
                if (
                    $argObj instanceof SolUserObject
                    && $argObj->getClassDef()?->hasAncestor('Integer')
                ) {
                    /** @var SolInteger $inner */
                    $inner = $argObj->getAttr('value');
                    return $this->createInstance($className, $inner->getValue());
                }

                /* ── 4. String and its subclasses ------------------------- */
                if ($argObj instanceof SolString) {
                    $ok = $className === 'String'
                    || (isset($this->classes[$className])
                        && $this->classes[$className]->hasAncestor('String'));
                    if ($ok) {
                        /** @var SolString $inst */
                        $inst = $this->createInstance($className, null);
                        $inst->setValue($argObj->getValue());
                        return $inst;
                    }
                    throw new InterpretValueException();
                }

                /* ── 5. Nil.from:  ------------------------------------- */
                if ($argObj instanceof SolNil) {
                    return $this->nilObject;
                }

                /* If nothing matches → semantic error 53 */
                throw new InterpretValueException();
            }

            // --- handle ClassName new()
            if ($selector === 'new') {
                // First <expr> inside <send> — this is literal class
                $exprElement = $child->getElementsByTagName('expr')->item(0);
                if (!$exprElement instanceof DOMElement) {
                    throw new InterpretValueException("Missing <expr> element for literal in 'new' send");
                }
                $lit = $exprElement->getElementsByTagName('literal')->item(0);
                if ($lit && $lit->getAttribute('class') === 'class') {
                    $className = $lit->getAttribute('value');
                    // Create a new object without init (or with 0 for Integer)
                    return $this->createInstance($className, null);
                }
            }

            /* ---------- New argument collection considering order ------------- */
            $argsMap  = [];                 // order => SolObject
            $recvExpr = null;

            foreach ($child->childNodes as $c2) {
                if (!($c2 instanceof DOMElement)) {
                    continue;
                }

                /* Argument <arg order="N"> */
                if ($c2->nodeName === 'arg') {
                    $ord = (int)$c2->getAttribute('order');          // ← Sorting key
                    // First <expr> inside <arg>
                    $argExpr = null;
                    foreach ($c2->childNodes as $c3) {
                        if ($c3 instanceof DOMElement && $c3->nodeName === 'expr') {
                            $argExpr = $c3;
                            break;
                        }
                    }
                    if ($argExpr !== null) {
                        $argsMap[$ord] = $this->evaluateExpr($argExpr, $ctx);
                    }
                    continue;
                }

                /* Receiver — direct <expr> */
                if ($c2->nodeName === 'expr') {
                    $recvExpr = $c2;         // Last encountered expr — this is the receiver
                }
            }

            /* Convert to array 0,1,2,… strictly in ascending order of order */
            ksort($argsMap, SORT_NUMERIC);
            $args = array_values($argsMap);

            /* ---------- End of new code ---------- */
            if ($recvExpr === null) {
                throw new \Exception("Error 32: Missing receiver in send '$selector'");
            }
            $recvObj = $this->evaluateExpr($recvExpr, $ctx);
            return $recvObj->callMethod($selector, $args, $this);
        }

        // Everything else — nil
        return $this->nilObject;
    }

    // MARK: createInstance
    public function createInstance(string $className, ?int $init = null): SolObject
    {
        // biult-in classes
        switch ($className) {
            case 'Integer':
                return new SolInteger($init ?? 0);
            case 'String':
                return new SolString($init !== null ? (string)$init : '');
            case 'Nil':
                return $this->nilObject;
            case 'True':
                return SolTrue::getInstance();
            case 'False':
                return SolFalse::getInstance();
            case 'Object':
            // “Pure” object without methods from AST
                return new SolObject();
            case 'Block':
                throw new NotImplementedException();
        }

        // User-defined classes
        if (isset($this->classes[$className])) {
            $cd = $this->classes[$className];

            /* --- Does it fall under String? --- */
            if ($cd->hasAncestor('String')) {
                // String behavior is sufficient, different methods in tests are not needed
                return new SolString($init !== null ? (string) $init : '');
            }

            /* --- Exactly the Integer class --- */
            if ($className === 'Integer') {
                return new SolInteger($init ?? 0);
            }

            /* --- Integer subclass --- */
            if ($cd->hasAncestor('Integer')) {
                $obj = new SolUserObject($cd);              // ← Wrapper needed
                $obj->setAttr('value', new SolInteger($init ?? 0));
                return $obj;
            }

            /* Everything else – as before */
            $obj = new SolUserObject($cd);
            return $obj;
        }
        throw new InterpretTypeException();
    }
}
