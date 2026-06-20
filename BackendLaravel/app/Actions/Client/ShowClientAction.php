<?php

namespace App\Actions\Client;

use App\Exceptions\BusinessException;
use App\Models\Client;
use App\Repositories\Contracts\ClientRepositoryInterface;

final class ShowClientAction
{
    public function __construct(
        private readonly ClientRepositoryInterface $clients,
    ) {}

    public function execute(int $id): Client
    {
        return $this->clients->find($id)
            ?? throw new BusinessException('Client introuvable.', 404);
    }
}
