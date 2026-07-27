<?php

namespace App\Repositories\Contracts;

use App\Models\RendezVous;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

interface RendezVousRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): RendezVous;

    public function paginateForClient(int $clientId, int $perPage = 15): LengthAwarePaginator;

    public function findForClient(int $id, int $clientId): ?RendezVous;

    /** Rendez-vous confirmés d'un centre pour une journée (clé = créneau ISO). */
    public function confirmedCountsForDay(int $centreSavId, Carbon $jour): Collection;

    /** Nombre de rendez-vous confirmés sur un créneau précis. */
    public function countForSlot(int $centreSavId, Carbon $creneau): int;
}
