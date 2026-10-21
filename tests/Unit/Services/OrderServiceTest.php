<?php

namespace Tests\Unit\Services;

use App\Exceptions\OrderHasPaymentsException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(OrderService::class);
    }

    /**
     * test create calculates line totals and order total
     */
    public function testCreate(): void
    {
        $user = User::factory()->create();

        $order = $this->service->createForUser($user->id, [
            'customer_name' => 'Mohamed',
            'customer_email' => 'Mohamed@example.com',
            'items' => [
                ['product_name' => 'Laptop', 'quantity' => 1, 'unit_price' => 1500],
                ['product_name' => 'Mouse', 'quantity' => 2, 'unit_price' => 50.5],
            ],
        ]);

        $this->assertSame('1601.00', $order->total_amount);
        $this->assertCount(2, $order->items);
        $this->assertSame('1500.00', $order->items[0]->line_total);
        $this->assertSame('101.00', $order->items[1]->line_total);
    }

    /**
     * test update replaces items and recalculates total
     */
    public function testUpdate(): void
    {
        $user = User::factory()->create();
        $order = $this->service->createForUser($user->id, [
            'customer_name' => 'Mohamed',
            'customer_email' => 'Mohamed@example.com',
            'items' => [
                ['product_name' => 'Old', 'quantity' => 1, 'unit_price' => 10],
            ],
        ]);

        $updated = $this->service->updateForUser($user->id, $order->id, [
            'customer_name' => 'Mohamed',
            'customer_email' => 'Mohamed@example.com',
            'status' => Order::STATUS_CONFIRMED,
            'items' => [
                ['product_name' => 'New', 'quantity' => 3, 'unit_price' => 20],
            ],
        ]);

        $this->assertSame(Order::STATUS_CONFIRMED, $updated->status);
        $this->assertSame('60.00', $updated->total_amount);
        $this->assertCount(1, $updated->items);
        $this->assertSame('New', $updated->items->first()->product_name);
    }

    /**
     * test delete throws when order has payments
     */
    public function testDeleteFails(): void
    {
        $user = User::factory()->create();
        $order = $this->service->createForUser($user->id, [
            'customer_name' => 'Mohamed',
            'customer_email' => 'Mohamed@example.com',
            'status' => Order::STATUS_CONFIRMED,
            'items' => [
                ['product_name' => 'Item', 'quantity' => 1, 'unit_price' => 100],
            ],
        ]);

        Payment::query()->create([
            'order_id' => $order->id,
            'gateway' => 'stripe',
            'amount' => $order->total_amount,
            'status' => Payment::STATUS_SUCCESSFUL,
            'transaction_reference' => 'TEST-REF',
        ]);

        $this->expectException(OrderHasPaymentsException::class);

        $this->service->deleteForUser($user->id, $order->id);
    }

    /**
     * test find for another user throws not found
     */
    public function testFindForbidden(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $order = $this->service->createForUser($owner->id, [
            'customer_name' => 'Owner',
            'customer_email' => 'owner@example.com',
            'items' => [
                ['product_name' => 'Item', 'quantity' => 1, 'unit_price' => 10],
            ],
        ]);

        $this->expectException(ModelNotFoundException::class);

        $this->service->findForUser($other->id, $order->id);
    }
}
