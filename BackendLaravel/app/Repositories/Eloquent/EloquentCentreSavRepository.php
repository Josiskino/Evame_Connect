<?php

namespace App\Repositories\Eloquent;

use App\Models\CentreSav;
use App\Repositories\Contracts\CentreSavRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentCentreSavRepository implements CentreSavRepositoryInterface
{
    public function allActive(): Collection
    {
        return CentreSav::where('actif', true)->orderBy('nom')->get();
    }

    public function findActive(int $id): ?CentreSav
    {
        return CentreSav::where('actif', true)->find($id);
    }
}
