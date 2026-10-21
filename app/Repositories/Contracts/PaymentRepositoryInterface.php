<?php

namespace App\Repositories\Contracts;

use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PaymentRepositoryInterface extends BaseRepositoryInterface
{
    public function paginateForUser(
        int $userId,
        ?int $orderId = null,
        ?string $status = null,
        int $perPage = 15
    ): LengthAwarePaginator;

    public function findForUser(int $userId, int|string $paymentId, array $relations = []): ?Payment;

    public function createPayment(array $data): Payment;
}
