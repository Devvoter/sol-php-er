<?php

/**
 * IPP - PHP Project Student
 *
 * @file SplTrue.php
 * @author Denys Pylypenko (xpylypd00)
 *
*/

namespace IPP\Student;

use IPP\Student\SolFalse;

class SolTrue extends SolObject
{
    private static ?SolTrue $instance = null;

    public function __construct()
    {
        parent::__construct(null);
    }

    public static function getInstance(): SolTrue
    {
        if (self::$instance === null) {
            self::$instance = new SolTrue();
        }
        return self::$instance;
    }

    /**
     * @param SolObject[] $args
     */
    public function callMethod(string $selector, array $args, Interpreter $intr): SolObject
    {
        switch ($selector) {
            case 'not':
                return SolFalse::getInstance();
            case 'and:':
                // if true -> execute the provided block
                $blk = $args[0];
                return $blk->callMethod('value', [], $intr);
            case 'or:':
                return self::getInstance();
            case 'ifTrue:ifFalse:':
                // first argument -> block for true
                $blkTrue = $args[0];
                return $blkTrue->callMethod('value', [], $intr);
        }
        return parent::callMethod($selector, $args, $intr);
    }
}
