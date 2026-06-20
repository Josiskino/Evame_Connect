<?php

namespace App\Actions\Vente;

use App\Exceptions\BusinessException;
use App\Models\Vente;
use App\Repositories\Contracts\VenteRepositoryInterface;

final class ShowVenteAction
{
    public function __construct(
        private readonly VenteRepositoryInterface $ventes,
    ) {}

    public function execute(int $id): Vente
    {
        return $this->ventes->find($id)
            ?? throw new BusinessException('Vente introuvable.', 404);
    }
}
