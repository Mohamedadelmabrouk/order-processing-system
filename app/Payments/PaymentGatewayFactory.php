<?php

namespace App\Payments;

use App\Contracts\Payments\PaymentGatewayInterface;
use App\Exceptions\InactiveGatewayException;
use App\Exceptions\UnsupportedGatewayException;
use App\Payments\Gateways\CreditCardGateway;
use App\Payments\Gateways\PaypalGateway;
use App\Payments\Gateways\StripeGateway;
use App\Repositories\Contracts\PaymentGatewayRepositoryInterface;

class PaymentGatewayFactory
{
    public function __construct(
        private readonly PaymentGatewayRepositoryInterface $gatewayRepository
    ) {
    }

    public function make(string $gatewayName): PaymentGatewayInterface
    {
        $gateway = $this->gatewayRepository->findByName($gatewayName);

        if (! $gateway) {
            throw new UnsupportedGatewayException("Payment gateway [{$gatewayName}] is not supported.");
        }

        if (! $gateway->is_active) {
            throw new InactiveGatewayException("Payment gateway [{$gatewayName}] is currently inactive.");
        }

        $config = array_merge(
            $this->envConfig($gatewayName),
            $gateway->config ?? []
        );

        return match ($gatewayName) {
            'credit_card' => new CreditCardGateway($config),
            'paypal' => new PaypalGateway($config),
            'stripe' => new StripeGateway($config),
            default => throw new UnsupportedGatewayException("Payment gateway [{$gatewayName}] is not supported."),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function envConfig(string $gatewayName): array
    {
        return match ($gatewayName) {
            'credit_card' => [
                'merchant_id' => config('services.credit_card.merchant_id'),
                'api_key' => config('services.credit_card.api_key'),
            ],
            'paypal' => [
                'client_id' => config('services.paypal.client_id'),
                'client_secret' => config('services.paypal.client_secret'),
            ],
            'stripe' => [
                'publishable_key' => config('services.stripe.key'),
                'secret_key' => config('services.stripe.secret'),
            ],
            default => [],
        };
    }
}
