<?php

namespace App\Repositories\Eloquent;

use App\Models\Entretien;
use App\Repositories\Contracts\EntretienRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentEntretienRepository implements EntretienRepositoryInterface
{
    public function forClient(int $clientId): Collection
    {
        return Entretien::where('client_id', $clientId)
            ->with('moto')
            ->orderBy('date_echeance')
            ->get();
    }
}
