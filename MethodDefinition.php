<?php

/**
 * IPP - PHP Project Student
 *
 * @file MethodDefinition.php
 * @author Denys Pylypenko (xpylypd00)
 *
 */

namespace IPP\Student;

use DOMElement;
use IPP\Student\SolObject;
use IPP\Student\Exception\InterpretTypeException;

class MethodDefinition
{
    private string $selector;
    private DOMElement $elem;

    public function __construct(DOMElement $elem)
    {
        $this->elem = $elem;
        $this->selector = $elem->getAttribute('selector');
    }

    public function getSelector(): string
    {
        return $this->selector;
    }

    /**
     * Immediately take the block and execute it.
     *
     * @param SolObject[] $args
     */
    public function execute(SolObject $self, array $args, Interpreter $intr): SolObject
    {

        /* 1) the <block> itself */
        $blockNode = $this->elem->getElementsByTagName('block')->item(0);
        if (!$blockNode instanceof DOMElement) {
            throw new \Exception("Error 35: No block body for {$this->selector}");
        }

        /* 2) --- parameters sorted by order --- */
        $tmp = [];  // order => name
        foreach ($blockNode->childNodes as $child) {
            if ($child instanceof DOMElement && $child->nodeName === 'parameter') {
                $ord = (int)$child->getAttribute('order');
                $tmp[$ord] = $child->getAttribute('name');
            }
        }
        ksort($tmp, SORT_NUMERIC);  // 1-2-3-…
        $paramNames = array_values($tmp); // [ name1, name2, … ]

        /* 3) check arity */
        if (count($paramNames) !== count($args)) {
            throw new InterpretTypeException();
        }

        /* 4) context and execution */
        $ctx = new ExecutionContext($self, $paramNames, $args);
        return $intr->interpretBlock($blockNode, $ctx);
    }
}
