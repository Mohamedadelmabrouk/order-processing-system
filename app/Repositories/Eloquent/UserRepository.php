<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?User
    {
        /** @var User|null $user */
        $user = $this->findBy('email', $email);

        return $user;
    }

    public function createUser(array $data): User
    {
        /** @var User $user */
        $user = $this->create($data);

        return $user;
    }
}
