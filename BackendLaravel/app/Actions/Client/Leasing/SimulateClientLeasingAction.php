<?php

namespace App\Actions\Client\Leasing;

use App\Exceptions\BusinessException;
use App\Repositories\Contracts\MotoRepositoryInterface;
use App\Support\LeasingCalculator;

/**
 * Cas d'usage : simuler un leasing à partir d'une moto (calcul live, non persisté).
 */
final class SimulateClientLeasingAction
{
    public function __construct(
        private readonly MotoRepositoryInterface $motos,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(int $motoId): array
    {
        $moto = $this->motos->find($motoId)
            ?? throw new BusinessException('Moto introuvable.', 404);

        if (! $moto->leasing_eligible) {
            throw new BusinessException("Cette moto n'est pas éligible au leasing.", 422);
        }

        return [
            'moto' => [
                'id' => $moto->id,
                'reference' => $moto->reference,
                'marque' => $moto->marque,
                'modele' => $moto->modele,
            ],
            ...LeasingCalculator::fromPrix((int) $moto->prix),
        ];
    }
}
