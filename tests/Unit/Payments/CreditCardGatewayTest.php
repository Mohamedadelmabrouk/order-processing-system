<?php

namespace Tests\Unit\Payments;

use App\Models\Payment;
use App\Payments\DTOs\PaymentPayload;
use App\Payments\Gateways\CreditCardGateway;
use PHPUnit\Framework\TestCase;

class CreditCardGatewayTest extends TestCase
{
    /**
     * test get name returns credit card
     */
    public function testGetName(): void
    {
        $this->assertSame('credit_card', (new CreditCardGateway)->getName());
    }

    /**
     * test charge returns successful simulated result
     */
    public function testCharge(): void
    {
        $gateway = new CreditCardGateway(['merchant_id' => 'demo']);
        $payload = new PaymentPayload(1, 100.50, 'customer@example.com');

        $result = $gateway->charge($payload);

        $this->assertSame(Payment::STATUS_SUCCESSFUL, $result->status);
        $this->assertNotEmpty($result->transactionReference);
        $this->assertStringStartsWith('CC-', $result->transactionReference);
        $this->assertTrue($result->meta['simulated']);
        $this->assertSame(1, $result->meta['order_id']);
        $this->assertSame(100.50, $result->meta['amount']);
    }
}
