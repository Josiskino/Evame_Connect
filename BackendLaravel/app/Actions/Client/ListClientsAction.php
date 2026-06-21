<?php

namespace App\Actions\Client;

use App\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListClientsAction
{
    public function __construct(
        private readonly ClientRepositoryInterface $clients,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function execute(?string $search = null, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->clients->paginate($search, $perPage, $filters);
    }
}
