<?php

namespace App\Services;

use App\Exceptions\OrderNotConfirmedException;
use App\Exceptions\PaymentProcessingException;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\DTOs\PaymentPayload;
use App\Payments\PaymentGatewayFactory;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Throwable;

class PaymentService
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly PaymentGatewayFactory $gatewayFactory,
    ) {
    }

    public function listForUser(
        int $userId,
        ?int $orderId = null,
        ?string $status = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        if ($orderId !== null) {
            $this->findOrderForUser($userId, $orderId);
        }

        return $this->paymentRepository->paginateForUser($userId, $orderId, $status, $perPage);
    }

    public function findForUser(int $userId, int|string $paymentId): Payment
    {
        $payment = $this->paymentRepository->findForUser($userId, $paymentId, ['order']);

        if (! $payment) {
            throw (new ModelNotFoundException)->setModel(Payment::class, [$paymentId]);
        }

        return $payment;
    }

    public function process(int $userId, int $orderId, string $gatewayName): Payment
    {
        $order = $this->findOrderForUser($userId, $orderId);

        if ($order->status !== Order::STATUS_CONFIRMED) {
            throw new OrderNotConfirmedException;
        }

        $gateway = $this->gatewayFactory->make($gatewayName);

        $payload = new PaymentPayload(
            orderId: (int) $order->id,
            amount: (float) $order->total_amount,
            customerEmail: $order->customer_email,
            currency: 'USD',
            metadata: [
                'user_id' => $userId,
                'customer_name' => $order->customer_name,
            ],
        );

        try {
            return DB::transaction(function () use ($gateway, $payload, $order, $gatewayName) {
                $result = $gateway->charge($payload);

                return $this->paymentRepository->createPayment([
                    'order_id' => $order->id,
                    'gateway' => $gatewayName,
                    'amount' => $order->total_amount,
                    'status' => $result->status,
                    'transaction_reference' => $result->transactionReference,
                    'meta' => array_merge($result->meta, [
                        'gateway_message' => $result->message,
                    ]),
                ]);
            });
        } catch (OrderNotConfirmedException|ModelNotFoundException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new PaymentProcessingException($e->getMessage() ?: 'Payment processing failed.');
        }
    }

    private function findOrderForUser(int $userId, int|string $orderId): Order
    {
        $order = $this->orderRepository->findForUser($userId, $orderId, ['items']);

        if (! $order) {
            throw (new ModelNotFoundException)->setModel(Order::class, [$orderId]);
        }

        return $order;
    }
}
