<?php

namespace App\Actions\Stock;

use App\Models\Moto;
use App\Models\StockMovement;
use App\Repositories\Contracts\MotoRepositoryInterface;

/**
 * Cas d'usage : décrémenter le stock d'une moto et journaliser le mouvement (out).
 * À appeler dans une transaction (la moto doit être verrouillée par l'appelant).
 */
final class DecrementStockAction
{
    public function __construct(
        private readonly MotoRepositoryInterface $motos,
        private readonly RecordStockMovementAction $recordMovement,
    ) {}

    public function execute(Moto $moto, int $quantite = 1, ?string $motif = null, ?string $reference = null, ?int $userId = null): void
    {
        $this->motos->decrementStock($moto, $quantite);

        $this->recordMovement->execute(
            motoId: $moto->id,
            type: StockMovement::TYPE_OUT,
            quantite: $quantite,
            motif: $motif,
            reference: $reference,
            userId: $userId,
        );
    }
}
