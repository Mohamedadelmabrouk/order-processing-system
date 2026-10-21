<?php

namespace Tests\Unit\Payments;

use App\Models\Payment;
use App\Payments\DTOs\PaymentResult;
use PHPUnit\Framework\TestCase;

class PaymentResultTest extends TestCase
{
    /**
     * test successful factory sets expected values
     */
    public function testSuccessful(): void
    {
        $result = PaymentResult::successful('REF-123', ['simulated' => true], 'OK');

        $this->assertSame(Payment::STATUS_SUCCESSFUL, $result->status);
        $this->assertSame('REF-123', $result->transactionReference);
        $this->assertTrue($result->isSuccessful());
        $this->assertSame('OK', $result->message);
        $this->assertTrue($result->meta['simulated']);
    }

    /**
     * test failed factory sets expected values
     */
    public function testFailed(): void
    {
        $result = PaymentResult::failed('Declined', ['code' => 'x'], 'REF-FAIL');

        $this->assertSame(Payment::STATUS_FAILED, $result->status);
        $this->assertFalse($result->isSuccessful());
        $this->assertSame('REF-FAIL', $result->transactionReference);
        $this->assertSame('Declined', $result->message);
    }
}
