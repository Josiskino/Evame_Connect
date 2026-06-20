<?php

namespace App\Actions\Moto;

use App\Exceptions\BusinessException;
use App\Models\Moto;
use App\Repositories\Contracts\MotoRepositoryInterface;

/**
 * Cas d'usage : récupérer le détail d'une moto.
 */
final class ShowMotoAction
{
    public function __construct(
        private readonly MotoRepositoryInterface $motos,
    ) {}

    public function execute(int $id): Moto
    {
        return $this->motos->find($id)
            ?? throw new BusinessException('Moto introuvable.', 404);
    }
}
