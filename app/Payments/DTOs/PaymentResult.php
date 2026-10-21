<?php

namespace App\Payments\DTOs;

use App\Models\Payment;

class PaymentResult
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $transactionReference = null,
        public readonly array $meta = [],
        public readonly string $message = '',
    ) {
    }

    public function isSuccessful(): bool
    {
        return $this->status === Payment::STATUS_SUCCESSFUL;
    }

    public static function successful(string $transactionReference, array $meta = [], string $message = 'Payment successful.'): self
    {
        return new self(
            status: Payment::STATUS_SUCCESSFUL,
            transactionReference: $transactionReference,
            meta: $meta,
            message: $message,
        );
    }

    public static function failed(string $message = 'Payment failed.', array $meta = [], ?string $transactionReference = null): self
    {
        return new self(
            status: Payment::STATUS_FAILED,
            transactionReference: $transactionReference,
            meta: $meta,
            message: $message,
        );
    }
}
