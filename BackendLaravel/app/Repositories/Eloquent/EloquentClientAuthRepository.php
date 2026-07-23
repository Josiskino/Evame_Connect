<?php

namespace App\Repositories\Eloquent;

use App\Models\Client;
use App\Repositories\Contracts\ClientAuthRepositoryInterface;

class EloquentClientAuthRepository implements ClientAuthRepositoryInterface
{
    public function findByPhone(string $phone): ?Client
    {
        return Client::query()->where('telephone', $phone)->first();
    }

    public function create(array $data): Client
    {
        return Client::create($data);
    }
}
