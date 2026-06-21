<?php

namespace App\Http\Requests\V1\Client;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            // Le téléphone est désormais obligatoire à l'enregistrement.
            'telephone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:255'],
            // Pièce d'identité (CNI).
            'cni_recto' => ['nullable', 'image', 'max:4096'],
            'cni_verso' => ['nullable', 'image', 'max:4096'],
            'cni_date_emission' => ['nullable', 'date'],
            // La CNI doit être valide : date d'expiration postérieure à aujourd'hui.
            'cni_date_expiration' => ['required', 'date', 'after:today'],
            'cni_lieu_emission' => ['nullable', 'string', 'max:255'],
        ];
    }
}
