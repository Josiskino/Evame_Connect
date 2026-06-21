<?php

namespace App\Repositories\Eloquent;

use App\Models\Client;
use App\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentClientRepository implements ClientRepositoryInterface
{
    public function paginate(?string $search, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Client::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('telephone', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('nom')->paginate($perPage);
    }

    public function stats(): array
    {
        return [
            'total' => Client::count(),
            'nouveaux_ce_mois' => Client::whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count(),
            'avec_cni' => Client::whereNotNull('cni_date_expiration')->count(),
        ];
    }

    public function create(array $data): Client
    {
        return Client::create($data);
    }

    public function find(int $id): ?Client
    {
        return Client::find($id);
    }
}
