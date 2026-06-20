<?php

namespace App\DTOs\Leasing;

final readonly class RegisterPaiementData
{
    public function __construct(
        public int $montant,
        public int $userId,
        public ?string $datePaiement = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, int $userId): self
    {
        return new self(
            montant: (int) $data['montant'],
            userId: $userId,
            datePaiement: $data['date_paiement'] ?? null,
        );
    }
}
