<?php

namespace App\Exceptions;

use Exception;

class InactiveGatewayException extends Exception
{
    public function __construct(string $message = 'The selected payment gateway is inactive.')
    {
        parent::__construct($message);
    }
}
