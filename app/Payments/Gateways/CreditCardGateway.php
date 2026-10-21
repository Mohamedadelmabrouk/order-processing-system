<?php

namespace App\Payments\Gateways;

use App\Contracts\Payments\PaymentGatewayInterface;
use App\Payments\DTOs\PaymentPayload;
use App\Payments\DTOs\PaymentResult;
use Illuminate\Support\Str;

class CreditCardGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly array $config = []
    ) {
    }

    public function getName(): string
    {
        return 'credit_card';
    }

    public function charge(PaymentPayload $payload): PaymentResult
    {
        $reference = 'CC-'.Str::upper(Str::random(12));

        return PaymentResult::successful($reference, [
            'gateway' => $this->getName(),
            'simulated' => true,
            'merchant_id' => $this->config['merchant_id'] ?? null,
            'order_id' => $payload->orderId,
            'amount' => $payload->amount,
            'currency' => $payload->currency,
            'card_brand' => 'visa',
            'last4' => '4242',
        ], 'Credit card payment processed successfully.');
    }
}
