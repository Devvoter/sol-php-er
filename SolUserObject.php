<?php

/**
 * IPP - PHP Project Student
 *
 * @file SolUserObject.php
 * @author Denys Pylypenko (xpylypd00)
 *
*/

namespace IPP\Student;

use IPP\Student\SolObject;
use IPP\Student\ClassDefinition;
use IPP\Student\Exception\InterpretValueException;
use IPP\Student\Exception\InterpretDnuException;

class SolUserObject extends SolObject
{
    public function __construct(ClassDefinition $classDef)
    {
        parent::__construct($classDef);
    }

    // MARK: callMethod
    /**
     * @param string $selector
     * @param SolObject[] $args
     * @param Interpreter $intr
     * @return SolObject
     */
    public function callMethod(string $selector, array $args, Interpreter $intr): SolObject
    {
        // Object's Class
        $cd = $this->getClassDef();

        // any subclass of Block
        if (
            $selector === 'whileTrue:'
            && $cd !== null && $cd->hasAncestor('Block')
            && count($args) === 1
        ) {
            $body = $args[0];

            // loop: while our block returns True on 'value'
            while (true) {
                // invoking 'value' method on itself (this)
                $cond = $this->callMethod('value', [], $intr);
                if (!($cond instanceof SolTrue)) {
                    break;
                }
                // execute body
                $body->callMethod('value', [], $intr);
            }
            // result of whileTrue: per spec - insignificant, returning nil is allowed
            return $intr->createInstance('Nil');
        }

        /* 1.  First – methods defined in AST (overrides) */
        if ($cd) {
            // -- searching for an exact match (as before)
            $m = $cd->getMethod($selector);

            if (!$m && str_ends_with($selector, ':')) {
                $m = $cd->getMethod(rtrim($selector, ':')); // try without colon
            }
            if ($m) {
                return $m->execute($this, $args, $intr); // found -> execute
            }
        }

        // 2.  Numeric branch – for subclasses of Integer
        if ($cd !== null && $cd->hasAncestor('Integer')) {
            /* ensure the object has an internal SolInteger */
            $selfInt = $this->getAttr('value');
            if (!$selfInt instanceof SolInteger) {
                $selfInt = new SolInteger(0);
                $this->setAttr('value', $selfInt);
            }

            /* build arguments for SolInteger */
            $intArgs = [];
            foreach ($args as $arg) {
                if ($arg instanceof SolInteger) {
                    $intArgs[] = $arg;
                } elseif (
                    $arg instanceof SolUserObject
                    && $arg->getClassDef() !== null
                    && $arg->getClassDef()->hasAncestor('Integer')
                ) {
                    $intArgs[] = $arg->getAttr('value'); // extract the "core"
                } elseif ($selector === 'timesRepeat:' && $arg instanceof SolBlock) {
                    $intArgs[] = $arg;
                } else {
                    throw new InterpretValueException();
                }
            }

            // delegate the message to the internal SolInteger
            $res = $selfInt->callMethod($selector, $intArgs, $intr);

            // pack the numeric result back into the subclass
            if ($res instanceof SolInteger) {
                return $intr->createInstance($cd->getName(), $res->getValue());
            }
            return $res;
        }
        // 3.  Everything else – delegate to base SolObject
        return parent::callMethod($selector, $args, $intr);
    }
}
