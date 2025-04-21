<?php

/**
 * IPP - PHP Project Student
 *
 * @file SolBlock.php
 * @author Denys Pylypenko (xpylypd00)
 *
*/

namespace IPP\Student;

use DOMElement;
use IPP\Student\ExecutionContext;
use IPP\Student\Exception\InterpretDnuException;

class SolBlock extends SolObject
{
    private DOMElement $node;
    private SolObject $capturedSelf;

    public function __construct(DOMElement $node, SolObject $self)
    {
        parent::__construct();
        $this->node = $node;
        $this->capturedSelf = $self;
    }

    public function callMethod(string $sel, array $args, Interpreter $intr): SolObject
    {

        if ($sel === 'isBlock' && count($args) === 0) {
            return SolTrue::getInstance();
        }

        $arity = (int)$this->node->getAttribute('arity');

        // zero-arity -> selector 'value'
        if ($arity === 0 && $sel === 'value' && count($args) === 0) {
            $ctx = new ExecutionContext($this->capturedSelf);
            return $intr->interpretBlock($this->node, $ctx);
        }

        // n-arity -> selector 'value:' repeated arity times
        if ($arity > 0) {
            $expected = str_repeat('value:', $arity);
            if ($sel === $expected && count($args) === $arity) {
                $ctx = new ExecutionContext($this->capturedSelf);
                // bind parameters
                foreach ($this->node->getElementsByTagName('parameter') as $p) {
                    $name = $p->getAttribute('name');
                    $ord  = (int)$p->getAttribute('order');
                    $ctx->setVar($name, $args[$ord - 1]);
                }
                return $intr->interpretBlock($this->node, $ctx);
            }
        }

        // whileTrue: — repeats the condition block until it returns True
        if ($sel === 'whileTrue:' && count($args) === 1) {
            $body = $args[0];
            if (!($body instanceof SolBlock)) {
                // the argument must be a block
                throw new InterpretDnuException($sel);
            }
            // $this is the condition block that repeats until True is not returned
            while (true) {
                $cond = $this->callMethod('value', [], $intr);
                // expect True/False as SolTrue/SolFalse; check truthiness:
                if (!$cond instanceof SolTrue) {
                    break;
                }
                // execute the body
                $body->callMethod('value', [], $intr);
            }
            // The result is not defined — return nil
            return $intr->createInstance('Nil');
        }
        throw new InterpretDnuException($sel);
    }
}
