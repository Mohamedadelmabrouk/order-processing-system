<?php

namespace App\Payments\DTOs;

class PaymentPayload
{
    public function __construct(
        public readonly int $orderId,
        public readonly float $amount,
        public readonly string $customerEmail,
        public readonly string $currency = 'USD',
        public readonly array $metadata = [],
    ) {
    }
}
