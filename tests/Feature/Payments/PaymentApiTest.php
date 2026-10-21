<?php

namespace Tests\Feature\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\User;
use Database\Seeders\PaymentGatewaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesJwtUser;

class PaymentApiTest extends TestCase
{
    use CreatesJwtUser;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PaymentGatewaySeeder::class);
    }

    /**
     * test payments require authentication
     */
    public function testPaymentsUnauthorized(): void
    {
        $this->getJson('/api/payments')->assertUnauthorized();
    }

    /**
     * test user can process payment for confirmed order
     */
    public function testProcessPayment(): void
    {
        $user = $this->actingAsJwt();
        $order = $this->createOrderFor($user, Order::STATUS_CONFIRMED, 1600);

        $response = $this->postJson('/api/payments', [
            'order_id' => $order->id,
            'gateway' => 'stripe',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', Payment::STATUS_SUCCESSFUL)
            ->assertJsonPath('data.gateway', 'stripe')
            ->assertJsonPath('data.amount', '1600.00');
    }

    /**
     * test payment is rejected for unconfirmed order
     */
    public function testProcessPaymentUnconfirmed(): void
    {
        $user = $this->actingAsJwt();
        $order = $this->createOrderFor($user, Order::STATUS_PENDING, 100);

        $this->postJson('/api/payments', [
            'order_id' => $order->id,
            'gateway' => 'paypal',
        ])->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    /**
     * test payment is rejected for another users order
     */
    public function testProcessPaymentForbidden(): void
    {
        $owner = User::factory()->create();
        $order = $this->createOrderFor($owner, Order::STATUS_CONFIRMED, 100);

        $this->actingAsJwt();

        $this->postJson('/api/payments', [
            'order_id' => $order->id,
            'gateway' => 'credit_card',
        ])->assertNotFound();
    }

    /**
     * test unsupported gateway fails validation
     */
    public function testUnsupportedGateway(): void
    {
        $user = $this->actingAsJwt();
        $order = $this->createOrderFor($user, Order::STATUS_CONFIRMED, 100);

        $this->postJson('/api/payments', [
            'order_id' => $order->id,
            'gateway' => 'bitcoin',
        ])->assertStatus(422);
    }

    /**
     * test inactive gateway returns validation error
     */
    public function testInactiveGateway(): void
    {
        PaymentGateway::query()->where('name', 'paypal')->update(['is_active' => false]);

        $user = $this->actingAsJwt();
        $order = $this->createOrderFor($user, Order::STATUS_CONFIRMED, 100);

        $this->postJson('/api/payments', [
            'order_id' => $order->id,
            'gateway' => 'paypal',
        ])->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    /**
     * test user can list payments filtered by order
     */
    public function testListPayments(): void
    {
        $user = $this->actingAsJwt();
        $order = $this->createOrderFor($user, Order::STATUS_CONFIRMED, 200);
        $otherOrder = $this->createOrderFor($user, Order::STATUS_CONFIRMED, 50);

        $this->postJson('/api/payments', ['order_id' => $order->id, 'gateway' => 'stripe']);
        $this->postJson('/api/payments', ['order_id' => $otherOrder->id, 'gateway' => 'paypal']);

        $response = $this->getJson('/api/payments?order_id='.$order->id);

        $response->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.order_id', $order->id);
    }

    /**
     * test user can show own payment and not others
     */
    public function testShowPayment(): void
    {
        $owner = User::factory()->create();
        $order = $this->createOrderFor($owner, Order::STATUS_CONFIRMED, 100);
        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'gateway' => 'stripe',
            'amount' => 100,
            'status' => Payment::STATUS_SUCCESSFUL,
            'transaction_reference' => 'OWN-1',
        ]);

        $this->actingAsJwt($owner);
        $this->getJson('/api/payments/'.$payment->id)
            ->assertOk()
            ->assertJsonPath('data.id', $payment->id);

        $this->actingAsJwt();
        $this->getJson('/api/payments/'.$payment->id)
            ->assertNotFound();
    }

    /**
     * test payment amount matches order total not request body
     */
    public function testPaymentAmount(): void
    {
        $user = $this->actingAsJwt();
        $order = $this->createOrderFor($user, Order::STATUS_CONFIRMED, 999.99);

        $response = $this->postJson('/api/payments', [
            'order_id' => $order->id,
            'gateway' => 'credit_card',
            'amount' => 1,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.amount', '999.99');
    }

    private function createOrderFor(User $user, string $status, float $total): Order
    {
        return Order::query()->create([
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'status' => $status,
            'total_amount' => $total,
        ]);
    }
}
