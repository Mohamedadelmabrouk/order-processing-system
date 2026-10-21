<?php

namespace Tests\Unit\Payments;

use App\Models\Payment;
use App\Payments\DTOs\PaymentPayload;
use App\Payments\Gateways\PaypalGateway;
use PHPUnit\Framework\TestCase;

class PaypalGatewayTest extends TestCase
{
    /**
     * test get name returns paypal
     */
    public function testGetName(): void
    {
        $this->assertSame('paypal', (new PaypalGateway)->getName());
    }

    /**
     * test charge returns successful simulated result
     */
    public function testCharge(): void
    {
        $gateway = new PaypalGateway(['client_id' => 'demo']);
        $payload = new PaymentPayload(2, 75.00, 'buyer@example.com');

        $result = $gateway->charge($payload);

        $this->assertSame(Payment::STATUS_SUCCESSFUL, $result->status);
        $this->assertNotEmpty($result->transactionReference);
        $this->assertStringStartsWith('PP-', $result->transactionReference);
        $this->assertTrue($result->meta['simulated']);
        $this->assertSame(2, $result->meta['order_id']);
        $this->assertSame('buyer@example.com', $result->meta['payer_email']);
    }
}
