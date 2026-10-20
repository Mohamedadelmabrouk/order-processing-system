<?php

namespace App\Repositories\Eloquent;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderRepository extends BaseRepository implements OrderRepositoryInterface
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
    }

    public function findForUser(int $userId, int|string $orderId, array $relations = []): ?Order
    {
        /** @var Order|null $order */
        $order = $this->model->newQuery()
            ->with($relations)
            ->where('user_id', $userId)
            ->whereKey($orderId)
            ->first();

        return $order;
    }

    public function paginateForUser(int $userId, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->with(['items'])
            ->where('user_id', $userId);

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->latest()->paginate($perPage);
    }

    public function createWithItems(array $orderData, array $items): Order
    {
        /** @var Order $order */
        $order = $this->model->newQuery()->create($orderData);
        $order->items()->createMany($items);

        return $order->load('items');
    }

    public function updateWithItems(Order $order, array $orderData, array $items): Order
    {
        $order->update($orderData);
        $order->items()->delete();
        $order->items()->createMany($items);

        return $order->fresh(['items']);
    }

    public function hasPayments(Order $order): bool
    {
        return $order->payments()->exists();
    }
}
