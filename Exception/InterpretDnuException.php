<?php

/**
 * IPP - PHP Project Student
 *
 * @file InterpretDnuException.php
 * @author Denys Pylypenko (xpylypd00)
 *
*/

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

class InterpretDnuException extends IPPException
{
    public function __construct(string $selector, ?Throwable $previous = null)
    {
        $message = "Error 51: the instance did not understand the message '{$selector}'";
        parent::__construct($message, ReturnCode::INTERPRET_DNU_ERROR, $previous);
    }
}
