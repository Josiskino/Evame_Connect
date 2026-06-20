<?php

namespace App\Actions\Stock;

use App\Models\StockMovement;
use App\Repositories\Contracts\StockMovementRepositoryInterface;

/**
 * Cas d'usage : journaliser un mouvement de stock (entrée ou sortie).
 */
final class RecordStockMovementAction
{
    public function __construct(
        private readonly StockMovementRepositoryInterface $movements,
    ) {}

    public function execute(
        int $motoId,
        string $type,
        int $quantite,
        ?string $motif = null,
        ?string $reference = null,
        ?int $userId = null,
    ): StockMovement {
        return $this->movements->record([
            'moto_id' => $motoId,
            'user_id' => $userId,
            'type' => $type,
            'quantite' => $quantite,
            'motif' => $motif,
            'reference' => $reference,
        ]);
    }
}
