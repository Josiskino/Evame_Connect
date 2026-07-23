<?php

namespace App\Repositories\Contracts;

use App\Models\ContratLeasing;
use Illuminate\Support\Collection;

interface GarageRepositoryInterface
{
    /** Motos possédées (dérivées des ventes + contrats). */
    public function motosForClient(int $clientId): Collection;

    /** Contrats de leasing du client (avec moto + paiements). */
    public function contratsForClient(int $clientId): Collection;

    /** Historique des paiements du client (via ses contrats). */
    public function paiementsForClient(int $clientId): Collection;

    public function garantiesForClient(int $clientId): Collection;

    public function documentsForClient(int $clientId): Collection;

    public function findContratForClient(int $id, int $clientId): ?ContratLeasing;
}
