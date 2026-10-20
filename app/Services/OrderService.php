<?php

namespace App\Services;

use App\Exceptions\OrderHasPaymentsException;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository
    ) {
    }

    public function listForUser(int $userId, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepository->paginateForUser($userId, $status, $perPage);
    }

    public function findForUser(int $userId, int|string $orderId): Order
    {
        $order = $this->orderRepository->findForUser($userId, $orderId, ['items']);

        if (! $order) {
            throw (new ModelNotFoundException)->setModel(Order::class, [$orderId]);
        }

        return $order;
    }

    public function createForUser(int $userId, array $data): Order
    {
        $preparedItems = $this->prepareItems($data['items']);

        $orderData = [
            'user_id' => $userId,
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'status' => $data['status'] ?? Order::STATUS_PENDING,
            'total_amount' => $this->calculateTotal($preparedItems),
        ];

        return DB::transaction(fn () => $this->orderRepository->createWithItems($orderData, $preparedItems));
    }

    public function updateForUser(int $userId, int|string $orderId, array $data): Order
    {
        $order = $this->findForUser($userId, $orderId);
        $preparedItems = $this->prepareItems($data['items']);

        $orderData = [
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'status' => $data['status'] ?? $order->status,
            'total_amount' => $this->calculateTotal($preparedItems),
        ];

        return DB::transaction(
            fn () => $this->orderRepository->updateWithItems($order, $orderData, $preparedItems)
        );
    }

    public function deleteForUser(int $userId, int|string $orderId): bool
    {
        $order = $this->findForUser($userId, $orderId);

        if ($this->orderRepository->hasPayments($order)) {
            throw new OrderHasPaymentsException;
        }

        return DB::transaction(fn () => $this->orderRepository->delete($order->id));
    }

    /**
     * @param  array<int, array{product_name: string, quantity: int|float|string, unit_price: int|float|string}>  $items
     * @return array<int, array{product_name: string, quantity: int, unit_price: float, line_total: float}>
     */
    private function prepareItems(array $items): array
    {
        return array_map(function (array $item): array {
            $quantity = (int) $item['quantity'];
            $unitPrice = round((float) $item['unit_price'], 2);
            $lineTotal = round($quantity * $unitPrice, 2);

            return [
                'product_name' => $item['product_name'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];
        }, $items);
    }

    /**
     * @param  array<int, array{line_total: float}>  $items
     */
    private function calculateTotal(array $items): float
    {
        return round(array_sum(array_column($items, 'line_total')), 2);
    }
}
