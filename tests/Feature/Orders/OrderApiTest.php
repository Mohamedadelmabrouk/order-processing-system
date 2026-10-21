<?php

namespace Tests\Feature\Orders;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesJwtUser;

class OrderApiTest extends TestCase
{
    use CreatesJwtUser;
    use RefreshDatabase;

    /**
     * test orders require authentication
     */
    public function testOrdersUnauthorized(): void
    {
        $this->getJson('/api/orders')->assertUnauthorized();
    }

    /**
     * test user can create order with calculated total
     */
    public function testCreateOrder(): void
    {
        $this->actingAsJwt();

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Mohamed Adel',
            'customer_email' => 'Mohamed@example.com',
            'items' => [
                ['product_name' => 'Laptop', 'quantity' => 1, 'unit_price' => 1500],
                ['product_name' => 'Mouse', 'quantity' => 2, 'unit_price' => 50],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_amount', '1600.00')
            ->assertJsonCount(2, 'data.items');
    }

    /**
     * test create order validation fails without items
     */
    public function testCreateOrderFails(): void
    {
        $this->actingAsJwt();

        $this->postJson('/api/orders', [
            'customer_name' => 'Mohamed Adel',
            'customer_email' => 'Mohamed@example.com',
            'items' => [],
        ])->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    /**
     * test user can list orders filtered by status
     */
    public function testListOrders(): void
    {
        $user = $this->actingAsJwt();

        Order::query()->create([
            'user_id' => $user->id,
            'customer_name' => 'A',
            'customer_email' => 'a@example.com',
            'status' => Order::STATUS_PENDING,
            'total_amount' => 10,
        ]);
        Order::query()->create([
            'user_id' => $user->id,
            'customer_name' => 'B',
            'customer_email' => 'b@example.com',
            'status' => Order::STATUS_CONFIRMED,
            'total_amount' => 20,
        ]);

        $response = $this->getJson('/api/orders?status=pending');

        $response->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.status', Order::STATUS_PENDING);
    }

    /**
     * test user can show own order
     */
    public function testShowOrder(): void
    {
        $user = $this->actingAsJwt();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'customer_name' => 'Mohamed',
            'customer_email' => 'Mohamed@example.com',
            'status' => Order::STATUS_PENDING,
            'total_amount' => 100,
        ]);

        $this->getJson('/api/orders/'.$order->id)
            ->assertOk()
            ->assertJsonPath('data.id', $order->id);
    }

    /**
     * test user cannot show another users order
     */
    public function testShowOrderForbidden(): void
    {
        $owner = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $owner->id,
            'customer_name' => 'Owner',
            'customer_email' => 'owner@example.com',
            'status' => Order::STATUS_PENDING,
            'total_amount' => 100,
        ]);

        $this->actingAsJwt();

        $this->getJson('/api/orders/'.$order->id)->assertNotFound();
    }

    /**
     * test user can update order and sync items
     */
    public function testUpdateOrder(): void
    {
        $this->actingAsJwt();

        $create = $this->postJson('/api/orders', [
            'customer_name' => 'Mohamed',
            'customer_email' => 'Mohamed@example.com',
            'items' => [
                ['product_name' => 'Old', 'quantity' => 1, 'unit_price' => 10],
            ],
        ])->json('data');

        $response = $this->putJson('/api/orders/'.$create['id'], [
            'customer_name' => 'Mohamed',
            'customer_email' => 'Mohamed@example.com',
            'status' => Order::STATUS_CONFIRMED,
            'items' => [
                ['product_name' => 'New', 'quantity' => 2, 'unit_price' => 40],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.total_amount', '80.00')
            ->assertJsonPath('data.status', Order::STATUS_CONFIRMED)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.product_name', 'New');
    }

    /**
     * test user can delete order without payments
     */
    public function testDeleteOrder(): void
    {
        $user = $this->actingAsJwt();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'customer_name' => 'Mohamed',
            'customer_email' => 'Mohamed@example.com',
            'status' => Order::STATUS_PENDING,
            'total_amount' => 100,
        ]);

        $this->deleteJson('/api/orders/'.$order->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    /**
     * test user cannot delete order with payments
     */
    public function testDeleteOrderFails(): void
    {
        $user = $this->actingAsJwt();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'customer_name' => 'Mohamed',
            'customer_email' => 'Mohamed@example.com',
            'status' => Order::STATUS_CONFIRMED,
            'total_amount' => 100,
        ]);

        Payment::query()->create([
            'order_id' => $order->id,
            'gateway' => 'stripe',
            'amount' => 100,
            'status' => Payment::STATUS_SUCCESSFUL,
            'transaction_reference' => 'REF-1',
        ]);

        $this->deleteJson('/api/orders/'.$order->id)
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }
}
