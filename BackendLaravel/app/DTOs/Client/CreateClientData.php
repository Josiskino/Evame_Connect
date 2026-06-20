<?php

namespace App\DTOs\Client;

final readonly class CreateClientData
{
    public function __construct(
        public string $nom,
        public ?string $telephone = null,
        public ?string $email = null,
        public ?string $adresse = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            nom: $data['nom'],
            telephone: $data['telephone'] ?? null,
            email: $data['email'] ?? null,
            adresse: $data['adresse'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'nom' => $this->nom,
            'telephone' => $this->telephone,
            'email' => $this->email,
            'adresse' => $this->adresse,
        ];
    }
}
