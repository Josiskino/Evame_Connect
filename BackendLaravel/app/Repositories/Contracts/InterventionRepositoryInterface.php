<?php

namespace App\Repositories\Contracts;

use App\Models\Commentaire;
use App\Models\Intervention;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface InterventionRepositoryInterface
{
    /** Dossiers SAV d'un client (espace client). */
    public function paginateForClient(int $clientId, int $perPage = 15): LengthAwarePaginator;

    public function findByNumeroForClient(string $numeroDossier, int $clientId): ?Intervention;

    /**
     * Liste filtrée (date du jour, statut, technicien assigné).
     *
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters, ?int $technicienId = null): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Intervention;

    public function find(int $id): ?Intervention;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Intervention $intervention, array $data): Intervention;

    /**
     * @param  array<string, mixed>  $data
     */
    public function addCommentaire(Intervention $intervention, array $data): Commentaire;
}
