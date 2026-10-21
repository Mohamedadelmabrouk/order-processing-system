<?php

namespace App\Payments\Gateways;

use App\Contracts\Payments\PaymentGatewayInterface;
use App\Payments\DTOs\PaymentPayload;
use App\Payments\DTOs\PaymentResult;
use Illuminate\Support\Str;

class StripeGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly array $config = []
    ) {
    }

    public function getName(): string
    {
        return 'stripe';
    }

    public function charge(PaymentPayload $payload): PaymentResult
    {
        $reference = 'ST-'.Str::upper(Str::random(12));

        return PaymentResult::successful($reference, [
            'gateway' => $this->getName(),
            'simulated' => true,
            'publishable_key' => $this->config['publishable_key'] ?? null,
            'order_id' => $payload->orderId,
            'amount' => $payload->amount,
            'currency' => $payload->currency,
            'charge_id' => 'ch_'.Str::lower(Str::random(16)),
        ], 'Stripe payment processed successfully.');
    }
}
