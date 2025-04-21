<?php

/**
 * IPP - PHP Project Student
 *
 * @file ParseUndefVar.php
 * @author Denys Pylypenko (xpylypd00)
 *
*/

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

class ParseUndefVar extends IPPException
{
    public function __construct(string $varName, ?Throwable $prev = null)
    {
        $message = "Error 32: Undefined variable '{$varName}'";
        parent::__construct($message, ReturnCode::PARSE_UNDEF_ERROR, $prev, false);
    }
}
