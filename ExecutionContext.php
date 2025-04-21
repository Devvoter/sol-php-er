<?php

/**
 * IPP - PHP Project Student
 *
 * @file ExecutionContext.php
 * @author Denys Pylypenko (xpylypd00)
 *
 */

namespace IPP\Student;

use IPP\Student\SolObject;
use IPP\Student\Exception\ParseUndefVar;
use IPP\Student\Exception\InterpretTypeException;

class ExecutionContext
{
    private SolObject $selfObject;

    // name => value
    /** @var SolObject[] */
    private array $variables = [];

    /**
     * @param SolObject $self       — self value inside block/method
     * @param string[]  $paramNames — names of parameters of the block
     * @param SolObject[] $args     — passed arguments
     */
    public function __construct(SolObject $self, array $paramNames = [], array $args = [])
    {

        $this->selfObject = $self;
        // initialize parameters
        foreach ($paramNames as $i => $name) {
            $this->variables[$name] = $args[$i];
        }
    }

    public function getSelf(): SolObject
    {
        return $this->selfObject;
    }

    public function getVar(string $name): SolObject
    {
        if (!array_key_exists($name, $this->variables)) {
            throw new ParseUndefVar($name);
        }
        return $this->variables[$name];
    }

    public function setVar(string $name, SolObject $value): void
    {
        // dont allow self/super, check for collisions with parameters
        if ($name === 'self' || $name === 'super') {
            throw new InterpretTypeException();
        }
        $this->variables[$name] = $value;
    }
}
