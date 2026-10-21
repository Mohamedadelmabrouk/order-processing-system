<?php

namespace App\Repositories\Contracts;

use App\Models\PaymentGateway;

interface PaymentGatewayRepositoryInterface extends BaseRepositoryInterface
{
    public function findByName(string $name): ?PaymentGateway;
}
