<?php

namespace App\DTOs\Client\Leasing;

use App\Models\DemandeLeasing;

/**
 * Données d'entrée d'une demande de leasing.
 */
final readonly class CreateDemandeLeasingData
{
    public function __construct(
        public int $clientId,
        public int $motoId,
        public string $frequence,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $frequence = $data['frequence'] ?? DemandeLeasing::FREQUENCE_JOURNALIER;

        return new self(
            clientId: (int) $data['client_id'],
            motoId: (int) $data['moto_id'],
            frequence: in_array($frequence, DemandeLeasing::FREQUENCES, true) ? $frequence : DemandeLeasing::FREQUENCE_JOURNALIER,
        );
    }
}
