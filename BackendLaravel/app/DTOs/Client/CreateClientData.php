<?php

namespace App\DTOs\Client;

final readonly class CreateClientData
{
    public function __construct(
        public string $nom,
        public ?string $telephone = null,
        public ?string $email = null,
        public ?string $adresse = null,
        public ?string $cni_recto = null,
        public ?string $cni_verso = null,
        public ?string $cni_date_emission = null,
        public ?string $cni_date_expiration = null,
        public ?string $cni_lieu_emission = null,
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
            cni_recto: $data['cni_recto'] ?? null,
            cni_verso: $data['cni_verso'] ?? null,
            cni_date_emission: $data['cni_date_emission'] ?? null,
            cni_date_expiration: $data['cni_date_expiration'] ?? null,
            cni_lieu_emission: $data['cni_lieu_emission'] ?? null,
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
            'cni_recto' => $this->cni_recto,
            'cni_verso' => $this->cni_verso,
            'cni_date_emission' => $this->cni_date_emission,
            'cni_date_expiration' => $this->cni_date_expiration,
            'cni_lieu_emission' => $this->cni_lieu_emission,
        ];
    }
}
