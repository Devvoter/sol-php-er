<?php

/**
 * IPP - PHP Project Student
 *
 * @file SolInteger.php
 * @author Denys Pylypenko (xpylypd00)
 *
*/

namespace IPP\Student;

use IPP\Student\SolObject;
use IPP\Student\SolString;
use IPP\Student\Interpreter;
use IPP\Student\Exception\InterpretValueException;

class SolInteger extends SolObject
{
    private int $value;

    public function __construct(int $value)
    {
        parent::__construct(null);
        $this->value = $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function setValue(int $value): void
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return (string)$this->value;
    }

    public function callMethod(string $selector, array $args, Interpreter $intr): SolObject
    {
        switch ($selector) {
            case 'plus:':
                /* not a number ->  error 53 */
                if (count($args) !== 1 || !($args[0] instanceof SolInteger)) {
                    throw new InterpretValueException();
                }
                return new SolInteger($this->value + $args[0]->getValue());
            case 'minus:':
                if (count($args) !== 1 || !($args[0] instanceof SolInteger)) {
                    throw new InterpretValueException();
                }
                return new SolInteger($this->value - $args[0]->getValue());
            case 'multiplyBy:':
                if (count($args) !== 1 || !($args[0] instanceof SolInteger)) {
                    throw new InterpretValueException();
                }
                return new SolInteger($this->value * $args[0]->getValue());
            case 'divBy:':
                if (count($args) !== 1 || !($args[0] instanceof SolInteger)) {
                    throw new InterpretValueException();
                }
                $b = $args[0]->getValue();
                if ($b === 0) {
                    throw new InterpretValueException();
                }
                return new SolInteger(intdiv($this->value, $b));
            case 'asString':
                return new SolString((string)$this->value);
            case 'asInteger':
                return $this;
            case 'equalTo:':
                return ($args[0] instanceof SolInteger && $this->value === $args[0]->getValue())
                    ? SolTrue::getInstance()
                    : SolFalse::getInstance();
            case 'greaterThan:':
                return ($args[0] instanceof SolInteger && $this->value > $args[0]->getValue())
                    ? SolTrue::getInstance()
                    : SolFalse::getInstance();
            case 'timesRepeat:':
                $n = $this->value;
                $blk = $args[0];
                for ($i = 1; $i <= $n; $i++) {
                    // the block takes one parameter – the iteration number
                    $blk->callMethod('value:', [ new SolInteger($i) ], $intr);
                }
                return $this;
            case 'isNumber':
                return SolTrue::getInstance();
            case 'isString':
            case 'isBlock':
            case 'isNil':
                return SolFalse::getInstance();
        }
        // Delegate everything else to SolObject
        return parent::callMethod($selector, $args, $intr);
    }
}
