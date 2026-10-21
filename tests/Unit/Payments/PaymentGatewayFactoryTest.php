<?php

namespace Tests\Unit\Payments;

use App\Exceptions\InactiveGatewayException;
use App\Exceptions\UnsupportedGatewayException;
use App\Models\PaymentGateway;
use App\Payments\Gateways\CreditCardGateway;
use App\Payments\Gateways\PaypalGateway;
use App\Payments\Gateways\StripeGateway;
use App\Payments\PaymentGatewayFactory;
use Database\Seeders\PaymentGatewaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentGatewayFactoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PaymentGatewaySeeder::class);
    }

    /**
     * test it resolves supported active gateways
     */
    public function testMake(): void
    {
        $factory = app(PaymentGatewayFactory::class);

        $this->assertInstanceOf(CreditCardGateway::class, $factory->make('credit_card'));
        $this->assertInstanceOf(PaypalGateway::class, $factory->make('paypal'));
        $this->assertInstanceOf(StripeGateway::class, $factory->make('stripe'));
    }

    /**
     * test it throws for unsupported gateway
     */
    public function testMakeUnsupported(): void
    {
        $this->expectException(UnsupportedGatewayException::class);

        app(PaymentGatewayFactory::class)->make('bitcoin');
    }

    /**
     * test it throws for inactive gateway
     */
    public function testMakeInactive(): void
    {
        PaymentGateway::query()->where('name', 'paypal')->update(['is_active' => false]);

        $this->expectException(InactiveGatewayException::class);

        app(PaymentGatewayFactory::class)->make('paypal');
    }
}
