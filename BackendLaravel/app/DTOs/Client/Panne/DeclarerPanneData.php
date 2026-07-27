<?php

namespace App\DTOs\Client\Panne;

/**
 * Données d'entrée d'une déclaration de panne.
 */
final readonly class DeclarerPanneData
{
    public function __construct(
        public int $clientId,
        public int $motoId,
        public string $categorie,
        public string $urgence,
        public string $description,
        public ?string $photoUrl,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            clientId: (int) $data['client_id'],
            motoId: (int) $data['moto_id'],
            categorie: $data['categorie'],
            urgence: $data['urgence'],
            description: $data['description'],
            photoUrl: $data['photo_url'] ?? null,
        );
    }
}
