<?php

/**
 * IPP - PHP Project Student
 *
 * @file InterpretValueException.php
 * @author Denys Pylypenko (xpylypd00)
 *
*/

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

class InterpretValueException extends IPPException
{
    public function __construct(string $message = "Error 53: bad value(division by 0, ..)", ?Throwable $previous = null)
    {
        parent::__construct($message, ReturnCode::INTERPRET_VALUE_ERROR, $previous);
    }
}
