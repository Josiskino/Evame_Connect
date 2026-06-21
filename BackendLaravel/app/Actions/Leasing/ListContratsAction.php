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
    /**
     * @param  array<string, mixed>  $filters
     */
    public function execute(bool $enRetardSeulement = false, int $perPage = 15, ?string $search = null, array $filters = [])
    {
        if ($enRetardSeulement) {
            return $this->contrats->activeWithPaiements()
                ->filter(fn ($c) => $c->en_retard)
                ->when($search, fn ($col) => $col->filter(
                    fn ($c) => str_contains(mb_strtolower($c->client?->nom ?? ''), mb_strtolower($search))
                ))
                ->when(! empty($filters['date_from']), fn ($col) => $col->filter(
                    fn ($c) => $c->date_debut?->format('Y-m-d') >= $filters['date_from']
                ))
                ->when(! empty($filters['date_to']), fn ($col) => $col->filter(
                    fn ($c) => $c->date_debut?->format('Y-m-d') <= $filters['date_to']
                ))
                ->values();
        }

        return $this->contrats->paginate($perPage, $search, $filters);
    }
}
