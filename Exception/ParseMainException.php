<?php

/**
 * IPP - PHP Project Student
 *
 * @file ParseMainException.php
 * @author Denys Pylypenko (xpylypd00)
 *
*/

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

class ParseMainException extends IPPException
{
    public function __construct(string $message = "Error 31: Class Main not found", ?Throwable $prev = null)
    {
        parent::__construct($message, ReturnCode::PARSE_MAIN_ERROR, $prev, false);
    }
}
