<?php

/**
 * IPP - PHP Project Student
 *
 * @file SolFalse.php
 * @author Denys Pylypenko (xpylypd00)
 *
*/

namespace IPP\Student;

class SolFalse extends SolObject
{
    private static ?SolFalse $instance = null;

    public function __construct()
    {
        parent::__construct(null);
    }

    public static function getInstance(): SolFalse
    {
        if (self::$instance === null) {
            self::$instance = new SolFalse();
        }
        return self::$instance;
    }

    public function callMethod(string $selector, array $args, Interpreter $intr): SolObject
    {
        switch ($selector) {
            case 'not':
                return SolTrue::getInstance();
            case 'and:':
                // if false -> immediately return false
                return self::getInstance();
            case 'or:':
                // if false -> execute the block
                $blk = $args[0];
                return $blk->callMethod('value', [], $intr);
            case 'ifTrue:ifFalse:':
                // second argument — block for false
                $blkFalse = $args[1];
                return $blkFalse->callMethod('value', [], $intr);
        }
        return parent::callMethod($selector, $args, $intr);
    }
}
