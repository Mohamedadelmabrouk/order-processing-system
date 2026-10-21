<?php

namespace Tests\Unit\Services;

use App\Exceptions\OrderNotConfirmedException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\OrderService;
use App\Services\PaymentService;
use Database\Seeders\PaymentGatewaySeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $paymentService;

    private OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PaymentGatewaySeeder::class);
        $this->paymentService = app(PaymentService::class);
        $this->orderService = app(OrderService::class);
    }

    /**
     * test process throws when order is not confirmed
     */
    public function testProcessUnconfirmed(): void
    {
        $user = User::factory()->create();
        $order = $this->orderService->createForUser($user->id, [
            'customer_name' => 'Mohamed',
            'customer_email' => 'Mohamed@example.com',
            'status' => Order::STATUS_PENDING,
            'items' => [
                ['product_name' => 'Item', 'quantity' => 1, 'unit_price' => 50],
            ],
        ]);

        $this->expectException(OrderNotConfirmedException::class);

        $this->paymentService->process($user->id, $order->id, 'stripe');
    }

    /**
     * test process throws when order belongs to another user
     */
    public function testProcessForbidden(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $order = $this->orderService->createForUser($owner->id, [
            'customer_name' => 'Owner',
            'customer_email' => 'owner@example.com',
            'status' => Order::STATUS_CONFIRMED,
            'items' => [
                ['product_name' => 'Item', 'quantity' => 1, 'unit_price' => 50],
            ],
        ]);

        $this->expectException(ModelNotFoundException::class);

        $this->paymentService->process($other->id, $order->id, 'paypal');
    }

    /**
     * test process creates payment with order total and keeps order status
     */
    public function testProcessPayment(): void
    {
        $user = User::factory()->create();
        $order = $this->orderService->createForUser($user->id, [
            'customer_name' => 'Mohamed',
            'customer_email' => 'Mohamed@example.com',
            'status' => Order::STATUS_CONFIRMED,
            'items' => [
                ['product_name' => 'Laptop', 'quantity' => 1, 'unit_price' => 1500],
                ['product_name' => 'Mouse', 'quantity' => 2, 'unit_price' => 50],
            ],
        ]);

        $payment = $this->paymentService->process($user->id, $order->id, 'credit_card');

        $this->assertSame(Payment::STATUS_SUCCESSFUL, $payment->status);
        $this->assertSame('credit_card', $payment->gateway);
        $this->assertSame('1600.00', $payment->amount);
        $this->assertNotEmpty($payment->transaction_reference);

        $order->refresh();
        $this->assertSame(Order::STATUS_CONFIRMED, $order->status);
    }
}
