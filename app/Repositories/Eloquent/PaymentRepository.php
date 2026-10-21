<?php

namespace App\Repositories\Eloquent;

use App\Models\Payment;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaymentRepository extends BaseRepository implements PaymentRepositoryInterface
{
    public function __construct(Payment $model)
    {
        parent::__construct($model);
    }

    public function paginateForUser(
        int $userId,
        ?int $orderId = null,
        ?string $status = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->model->newQuery()
            ->with(['order'])
            ->whereHas('order', fn ($q) => $q->where('user_id', $userId));

        if ($orderId !== null) {
            $query->where('order_id', $orderId);
        }

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->latest()->paginate($perPage);
    }

    public function findForUser(int $userId, int|string $paymentId, array $relations = []): ?Payment
    {
        /** @var Payment|null $payment */
        $payment = $this->model->newQuery()
            ->with($relations)
            ->whereKey($paymentId)
            ->whereHas('order', fn ($q) => $q->where('user_id', $userId))
            ->first();

        return $payment;
    }

    public function createPayment(array $data): Payment
    {
        /** @var Payment $payment */
        $payment = $this->create($data);

        return $payment->load('order');
    }
}
