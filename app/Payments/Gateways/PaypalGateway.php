<?php

namespace App\Payments\Gateways;

use App\Contracts\Payments\PaymentGatewayInterface;
use App\Payments\DTOs\PaymentPayload;
use App\Payments\DTOs\PaymentResult;
use Illuminate\Support\Str;

class PaypalGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly array $config = []
    ) {
    }

    public function getName(): string
    {
        return 'paypal';
    }

    public function charge(PaymentPayload $payload): PaymentResult
    {
        $reference = 'PP-'.Str::upper(Str::random(12));

        return PaymentResult::successful($reference, [
            'gateway' => $this->getName(),
            'simulated' => true,
            'client_id' => $this->config['client_id'] ?? null,
            'order_id' => $payload->orderId,
            'amount' => $payload->amount,
            'currency' => $payload->currency,
            'payer_email' => $payload->customerEmail,
        ], 'PayPal payment processed successfully.');
    }
}
