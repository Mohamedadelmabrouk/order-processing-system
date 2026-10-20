<?php

namespace App\Repositories\Contracts;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface extends BaseRepositoryInterface
{
    public function findForUser(int $userId, int|string $orderId, array $relations = []): ?Order;

    public function paginateForUser(int $userId, ?string $status = null, int $perPage = 15): LengthAwarePaginator;

    public function createWithItems(array $orderData, array $items): Order;

    public function updateWithItems(Order $order, array $orderData, array $items): Order;

    public function hasPayments(Order $order): bool;
}
