<?php

namespace App\Contracts\Payments;

use App\Payments\DTOs\PaymentPayload;
use App\Payments\DTOs\PaymentResult;

interface PaymentGatewayInterface
{
    public function getName(): string;

    public function charge(PaymentPayload $payload): PaymentResult;
}
