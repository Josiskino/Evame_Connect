<?php

namespace App\Actions\Client;

use App\DTOs\Client\CreateClientData;
use App\Models\Client;
use App\Repositories\Contracts\ClientRepositoryInterface;

final class CreateClientAction
{
    public function __construct(
        private readonly ClientRepositoryInterface $clients,
    ) {}

    public function execute(CreateClientData $data): Client
    {
        return $this->clients->create($data->toArray());
    }
}
