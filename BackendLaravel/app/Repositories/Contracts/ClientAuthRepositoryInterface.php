<?php

namespace App\Repositories\Contracts;

use App\Models\Client;

interface ClientAuthRepositoryInterface
{
    public function findByPhone(string $phone): ?Client;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Client;
}
