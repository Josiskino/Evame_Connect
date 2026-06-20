<?php

namespace App\DTOs\Leasing;

final readonly class CreateContratData
{
    public function __construct(
        public int $clientId,
        public int $motoId,
        public string $dateDebut,
        public int $dureeJours,
        public int $montantJournalier,
        public string $frequence,
        public ?int $venteId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            clientId: (int) $data['client_id'],
            motoId: (int) $data['moto_id'],
            dateDebut: $data['date_debut'],
            dureeJours: (int) $data['duree_jours'],
            montantJournalier: (int) $data['montant_journalier'],
            frequence: $data['frequence'],
            venteId: isset($data['vente_id']) ? (int) $data['vente_id'] : null,
        );
    }
}
