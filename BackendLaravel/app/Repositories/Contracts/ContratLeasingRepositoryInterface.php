<?php

namespace App\Repositories\Contracts;

use App\Models\ContratLeasing;
use App\Models\Paiement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ContratLeasingRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /** Contrats actifs avec leurs paiements (pour calcul des indicateurs). */
    public function activeWithPaiements(): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ContratLeasing;

    public function find(int $id): ?ContratLeasing;

    /**
     * @param  array<string, mixed>  $data
     */
    public function addPaiement(ContratLeasing $contrat, array $data): Paiement;
}
