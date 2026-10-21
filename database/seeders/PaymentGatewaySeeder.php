<?php

namespace Database\Seeders;

use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    public function run(): void
    {
        $gateways = [
            [
                'name' => 'credit_card',
                'display_name' => 'Credit Card',
                'is_active' => true,
                'config' => [
                    'merchant_id' => 'demo-merchant',
                ],
            ],
            [
                'name' => 'paypal',
                'display_name' => 'PayPal',
                'is_active' => true,
                'config' => [
                    'mode' => 'sandbox',
                ],
            ],
            [
                'name' => 'stripe',
                'display_name' => 'Stripe',
                'is_active' => true,
                'config' => [
                    'mode' => 'test',
                ],
            ],
        ];

        foreach ($gateways as $gateway) {
            PaymentGateway::query()->updateOrCreate(
                ['name' => $gateway['name']],
                $gateway
            );
        }
    }
}
