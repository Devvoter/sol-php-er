<?php

/**
 * IPP - PHP Project Student
 *
 * @file SolString.php
 * @author Denys Pylypenko (xpylypd00)
 *
*/

namespace IPP\Student;

use IPP\Student\SolObject;
use IPP\Student\Interpreter;

class SolString extends SolObject
{
    private string $value;

    public function __construct(string $value)
    {
        parent::__construct();
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * @param SolObject[] $args
     */
    public function callMethod(string $selector, array $args, Interpreter $intr): SolObject
    {
        switch ($selector) {
            case 'print':
                // Output the string content without adding a newline
                $intr->getStdOut()->writeString($this->value);
                return $this;
            case 'equalTo:':
                if (!($args[0] instanceof SolString)) {   // Ensure $args[0] is a SolString
                    return SolFalse::getInstance();
                }
                return ($this->value === $args[0]->getValue())
                    ? SolTrue::getInstance() : SolFalse::getInstance();
            case 'asString':
                return $this;
            case 'asInteger':
                // Only whole numbers (possibly with sign)
                if (preg_match('/^-?\d+$/', $this->value)) {
                    return new SolInteger((int)$this->value);
                }
                return $intr->createInstance('Nil');
            case 'isString':
                return SolTrue::getInstance();
            case 'concatenateWith:':
                if (!($args[0] instanceof SolString)) {
                    return $intr->createInstance('Nil');
                }
                return new SolString($this->value . $args[0]->getValue());
            case 'startsWith:endsBefore:':
                // two arguments, both SolInteger
                if (
                    !isset($args[0], $args[1])
                    || !($args[0] instanceof SolInteger)
                    || !($args[1] instanceof SolInteger)
                ) {
                    return $intr->createInstance('Nil');
                }
                $start = $args[0]->getValue();
                $end   = $args[1]->getValue();
                // must be positive and non-zero
                if ($start < 1 || $end < 1) {
                    return $intr->createInstance('Nil');
                }
                $length = $end - $start;
                if ($length <= 0) {
                    return new SolString('');
                }
                $substr = mb_substr($this->value, $start - 1, $length);
                return new SolString($substr);
        }
        // Everything else is delegated to SolObject
        return parent::callMethod($selector, $args, $intr);
    }
}
