<?php

namespace App\DTOs\Client\Auth;

/**
 * Données de modification du profil client (« Modification éventuelle »).
 * Seuls les champs fournis sont mis à jour.
 */
final readonly class UpdateClientProfileData
{
    public function __construct(
        public ?string $nom,
        public ?string $email,
        public ?string $ville,
        public ?string $quartier,
        public ?string $photoUrl,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            nom: $data['nom'] ?? null,
            email: $data['email'] ?? null,
            ville: $data['ville'] ?? null,
            quartier: $data['quartier'] ?? null,
            photoUrl: $data['photo_url'] ?? null,
        );
    }

    /**
     * Champs non-null à appliquer (snake_case DB).
     *
     * @return array<string, mixed>
     */
    public function toUpdateArray(): array
    {
        return array_filter([
            'nom' => $this->nom,
            'email' => $this->email,
            'ville' => $this->ville,
            'quartier' => $this->quartier,
            'photo_url' => $this->photoUrl,
        ], fn ($v) => $v !== null);
    }
}
