<?php

/**
 * IPP - PHP Project Student
 *
 * @file InterpretTypeException.php
 * @author Denys Pylypenko (xpylypd00)
 *
*/

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

class InterpretTypeException extends IPPException
{
    public function __construct(string $message = "Error 52: others errors", ?Throwable $previous = null)
    {
        parent::__construct($message, ReturnCode::INTERPRET_TYPE_ERROR, $previous);
    }
}
