<?php

namespace App\Actions\Leasing;

use App\Exceptions\BusinessException;
use App\Models\ContratLeasing;
use App\Repositories\Contracts\ContratLeasingRepositoryInterface;

final class ShowContratAction
{
    public function __construct(
        private readonly ContratLeasingRepositoryInterface $contrats,
    ) {}

    public function execute(int $id): ContratLeasing
    {
        return $this->contrats->find($id)
            ?? throw new BusinessException('Contrat de leasing introuvable.', 404);
    }
}
