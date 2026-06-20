<?php

namespace App\DTOs\Intervention;

final readonly class CreateInterventionData
{
    public function __construct(
        public int $clientId,
        public string $probleme,
        public ?int $motoId = null,
        public ?int $technicienId = null,
        public ?string $statut = null,
        public ?string $dateIntervention = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            clientId: (int) $data['client_id'],
            probleme: $data['probleme'],
            motoId: isset($data['moto_id']) ? (int) $data['moto_id'] : null,
            technicienId: isset($data['technicien_id']) ? (int) $data['technicien_id'] : null,
            statut: $data['statut'] ?? null,
            dateIntervention: $data['date_intervention'] ?? null,
        );
    }
}
