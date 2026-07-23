<?php

namespace App\Repositories\Eloquent;

use App\Models\ContratLeasing;
use App\Models\Document;
use App\Models\Garantie;
use App\Models\Moto;
use App\Models\Paiement;
use App\Models\Vente;
use App\Repositories\Contracts\GarageRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentGarageRepository implements GarageRepositoryInterface
{
    public function motosForClient(int $clientId): Collection
    {
        $ids = Vente::where('client_id', $clientId)->pluck('moto_id')
            ->merge(ContratLeasing::where('client_id', $clientId)->pluck('moto_id'))
            ->filter()
            ->unique()
            ->values();

        return Moto::whereIn('id', $ids)->orderBy('modele')->get();
    }

    public function contratsForClient(int $clientId): Collection
    {
        return ContratLeasing::where('client_id', $clientId)
            ->with(['moto', 'paiements'])
            ->latest('id')
            ->get();
    }

    public function paiementsForClient(int $clientId): Collection
    {
        return Paiement::whereHas('contrat', fn ($q) => $q->where('client_id', $clientId))
            ->orderByDesc('date_paiement')
            ->get();
    }

    public function garantiesForClient(int $clientId): Collection
    {
        return Garantie::where('client_id', $clientId)->with('moto')->get();
    }

    public function documentsForClient(int $clientId): Collection
    {
        return Document::where('client_id', $clientId)->orderByDesc('date')->get();
    }

    public function findContratForClient(int $id, int $clientId): ?ContratLeasing
    {
        return ContratLeasing::where('id', $id)
            ->where('client_id', $clientId)
            ->with(['moto', 'paiements'])
            ->first();
    }
}
