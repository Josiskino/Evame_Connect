<?php

namespace App\Repositories\Eloquent;

use App\Models\ContratLeasing;
use App\Models\Paiement;
use App\Repositories\Contracts\ContratLeasingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentContratLeasingRepository implements ContratLeasingRepositoryInterface
{
    public function paginate(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return ContratLeasing::with(['client', 'moto', 'paiements'])
            ->when($search, fn ($q) => $q->whereHas('client', fn ($c) => $c->where('nom', 'like', "%{$search}%")))
            ->latest()
            ->paginate($perPage);
    }

    public function activeWithPaiements(): Collection
    {
        return ContratLeasing::where('statut', 'actif')
            ->with('paiements')
            ->get();
    }

    public function stats(): array
    {
        $actifs = $this->activeWithPaiements();

        return [
            'contrats_actifs' => $actifs->count(),
            'encaissements_total' => (int) Paiement::sum('montant'),
            'clients_en_retard' => $actifs->filter(fn (ContratLeasing $c) => $c->en_retard)->count(),
            'reste_a_recouvrer' => (int) $actifs->sum('montant_restant'),
        ];
    }

    public function create(array $data): ContratLeasing
    {
        return ContratLeasing::create($data);
    }

    public function find(int $id): ?ContratLeasing
    {
        return ContratLeasing::with(['client', 'moto', 'paiements'])->find($id);
    }

    public function addPaiement(ContratLeasing $contrat, array $data): Paiement
    {
        return $contrat->paiements()->create($data);
    }
}
