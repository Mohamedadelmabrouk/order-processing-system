<?php

namespace App\Exceptions;

use Exception;

class UnsupportedGatewayException extends Exception
{
    public function __construct(string $message = 'The selected payment gateway is not supported.')
    {
        parent::__construct($message);
    }
}
