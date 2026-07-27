<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface EntretienRepositoryInterface
{
    /** Rappels d'entretien du client, triés par échéance. */
    public function forClient(int $clientId): Collection;
}
