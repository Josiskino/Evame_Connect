<?php

namespace App\Actions\Client;

use App\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListClientsAction
{
    public function __construct(
        private readonly ClientRepositoryInterface $clients,
    ) {}

    public function execute(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->clients->paginate($search, $perPage);
    }
}
