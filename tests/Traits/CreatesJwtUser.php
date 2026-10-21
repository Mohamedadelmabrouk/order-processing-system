<?php

namespace Tests\Traits;

use App\Models\User;

trait CreatesJwtUser
{
    protected function createUser(array $overrides = []): User
    {
        return User::factory()->create($overrides);
    }

    protected function actingAsJwt(?User $user = null): User
    {
        $user ??= $this->createUser();

        $token = auth('api')->login($user);

        $this->withHeader('Authorization', 'Bearer '.$token);
        $this->withHeader('Accept', 'application/json');

        return $user;
    }

    protected function authHeaders(?User $user = null): array
    {
        $user ??= $this->createUser();
        $token = auth('api')->login($user);

        return [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ];
    }
}
