<?php

namespace Tests\Unit\Payments;

use App\Models\Payment;
use App\Payments\DTOs\PaymentPayload;
use App\Payments\Gateways\StripeGateway;
use PHPUnit\Framework\TestCase;

class StripeGatewayTest extends TestCase
{
    /**
     * test get name returns stripe
     */
    public function testGetName(): void
    {
        $this->assertSame('stripe', (new StripeGateway)->getName());
    }

    /**
     * test charge returns successful simulated result
     */
    public function testCharge(): void
    {
        $gateway = new StripeGateway(['publishable_key' => 'pk_test']);
        $payload = new PaymentPayload(3, 200.00, 'payer@example.com');

        $result = $gateway->charge($payload);

        $this->assertSame(Payment::STATUS_SUCCESSFUL, $result->status);
        $this->assertNotEmpty($result->transactionReference);
        $this->assertStringStartsWith('ST-', $result->transactionReference);
        $this->assertTrue($result->meta['simulated']);
        $this->assertSame(3, $result->meta['order_id']);
        $this->assertArrayHasKey('charge_id', $result->meta);
    }
}
