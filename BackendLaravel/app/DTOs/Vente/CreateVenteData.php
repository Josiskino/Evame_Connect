<?php

namespace App\DTOs\Vente;

final readonly class CreateVenteData
{
    public function __construct(
        public int $clientId,
        public int $motoId,
        public int $userId,
        public string $mode,
        public ?int $montant = null,
        public ?string $dateVente = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, int $userId): self
    {
        return new self(
            clientId: (int) $data['client_id'],
            motoId: (int) $data['moto_id'],
            userId: $userId,
            mode: $data['mode'],
            montant: isset($data['montant']) ? (int) $data['montant'] : null,
            dateVente: $data['date_vente'] ?? null,
        );
    }
}
