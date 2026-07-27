<?php

namespace App\Repositories\Eloquent;

use App\Models\Commentaire;
use App\Models\Intervention;
use App\Repositories\Contracts\InterventionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EloquentInterventionRepository implements InterventionRepositoryInterface
{
    public function paginateForClient(int $clientId, int $perPage = 15): LengthAwarePaginator
    {
        return Intervention::where('client_id', $clientId)
            ->with('moto')
            ->latest('id')
            ->paginate($perPage);
    }

    public function findByNumeroForClient(string $numeroDossier, int $clientId): ?Intervention
    {
        return Intervention::where('numero_dossier', $numeroDossier)
            ->where('client_id', $clientId)
            ->with('moto')
            ->first();
    }

    public function list(array $filters, ?int $technicienId = null): Collection
    {
        $query = Intervention::with(['client', 'moto', 'technicien']);

        if (($filters['date'] ?? null) === 'today') {
            $query->whereDate('date_intervention', Carbon::today());
        }

        if (! empty($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }

        if ($technicienId !== null) {
            $query->where('technicien_id', $technicienId);
        }

        return $query->latest('date_intervention')->get();
    }

    public function create(array $data): Intervention
    {
        return Intervention::create($data);
    }

    public function find(int $id): ?Intervention
    {
        return Intervention::with(['client', 'moto', 'technicien', 'commentaires.user'])->find($id);
    }

    public function update(Intervention $intervention, array $data): Intervention
    {
        $intervention->update($data);

        return $intervention->load(['client', 'moto', 'technicien', 'commentaires.user']);
    }

    public function addCommentaire(Intervention $intervention, array $data): Commentaire
    {
        return $intervention->commentaires()->create($data);
    }
}
