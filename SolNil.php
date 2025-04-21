<?php

/**
 * IPP - PHP Project Student
 *
 * @file SolNil.php
 * @author Denys Pylypenko (xpylypd00)
 *
*/

namespace IPP\Student;

use IPP\Student\SolObject;
use IPP\Student\SolString;
use IPP\Student\Interpreter;

class SolNil extends SolObject
{
    public function __construct()
    {
        parent::__construct();
    }

    public function callMethod(string $selector, array $args, Interpreter $intr): SolObject
    {
        if ($selector === 'isNil' && count($args) === 0) {
            return SolTrue::getInstance();
        }
        // single responsibility: asString to avoid print(nil) crash
        if ($selector === 'asString') {
            return new SolString('nil');
        }

        // For all other methods, nil behaves like a regular object with no methods
        return parent::callMethod($selector, $args, $intr);
    }
}
