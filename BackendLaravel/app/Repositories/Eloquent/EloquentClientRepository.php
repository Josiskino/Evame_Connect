<?php

namespace App\Repositories\Eloquent;

use App\Models\Client;
use App\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentClientRepository implements ClientRepositoryInterface
{
    public function paginate(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        $query = Client::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('telephone', 'like', "%{$search}%");
            });
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
