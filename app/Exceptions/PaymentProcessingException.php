<?php

namespace App\Exceptions;

use Exception;

class PaymentProcessingException extends Exception
{
    public function __construct(string $message = 'Payment processing failed.')
    {
        parent::__construct($message);
    }
}
