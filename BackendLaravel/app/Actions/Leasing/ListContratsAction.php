<?php

namespace App\Actions\Leasing;

use App\Repositories\Contracts\ContratLeasingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class ListContratsAction
{
    public function __construct(
        private readonly ContratLeasingRepositoryInterface $contrats,
    ) {}

    /**
     * @return LengthAwarePaginator|Collection
     */
    public function execute(bool $enRetardSeulement = false, int $perPage = 15)
    {
        if ($enRetardSeulement) {
            return $this->contrats->activeWithPaiements()
                ->filter(fn ($c) => $c->en_retard)
                ->values();
        }

        return $this->contrats->paginate($perPage);
    }
}
