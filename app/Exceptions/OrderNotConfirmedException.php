<?php

namespace App\Exceptions;

use Exception;

class OrderNotConfirmedException extends Exception
{
    public function __construct(string $message = 'Payments can only be processed for confirmed orders.')
    {
        parent::__construct($message);
    }
}
