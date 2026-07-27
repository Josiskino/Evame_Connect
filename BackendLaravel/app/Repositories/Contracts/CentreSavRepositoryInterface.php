<?php

namespace App\Repositories\Contracts;

use App\Models\CentreSav;
use Illuminate\Support\Collection;

interface CentreSavRepositoryInterface
{
    /** Centres actifs. */
    public function allActive(): Collection;

    public function findActive(int $id): ?CentreSav;
}
