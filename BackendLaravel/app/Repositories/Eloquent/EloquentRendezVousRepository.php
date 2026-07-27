<?php

namespace App\Repositories\Eloquent;

use App\Models\RendezVous;
use App\Repositories\Contracts\RendezVousRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EloquentRendezVousRepository implements RendezVousRepositoryInterface
{
    public function create(array $data): RendezVous
    {
        return RendezVous::create($data);
    }

    public function paginateForClient(int $clientId, int $perPage = 15): LengthAwarePaginator
    {
        return RendezVous::where('client_id', $clientId)
            ->with(['centre', 'intervention'])
            ->orderByDesc('creneau')
            ->paginate($perPage);
    }

    public function findForClient(int $id, int $clientId): ?RendezVous
    {
        return RendezVous::where('id', $id)
            ->where('client_id', $clientId)
            ->with(['centre', 'intervention'])
            ->first();
    }

    public function confirmedCountsForDay(int $centreSavId, Carbon $jour): Collection
    {
        return RendezVous::query()
            ->where('centre_sav_id', $centreSavId)
            ->where('statut', RendezVous::STATUT_CONFIRME)
            ->whereDate('creneau', $jour->toDateString())
            ->get()
            ->groupBy(fn (RendezVous $rdv) => $rdv->creneau->format('H:i'))
            ->map->count();
    }

    public function countForSlot(int $centreSavId, Carbon $creneau): int
    {
        return RendezVous::query()
            ->where('centre_sav_id', $centreSavId)
            ->where('statut', RendezVous::STATUT_CONFIRME)
            ->where('creneau', $creneau)
            ->count();
    }
}
