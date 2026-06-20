<?php

namespace App\Http\Requests\V1\Vente;

use App\Models\Vente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVenteRequest extends FormRequest
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
            'client_id' => ['required', 'exists:clients,id'],
            'moto_id' => ['required', 'exists:motos,id'],
            'mode' => ['required', Rule::in([Vente::MODE_DIRECT, Vente::MODE_LEASING])],
            'montant' => ['nullable', 'integer', 'min:0'],
            'date_vente' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'Veuillez sélectionner un client.',
            'moto_id.required' => 'Veuillez sélectionner une moto.',
            'mode.required' => "Veuillez choisir le mode d'achat.",
        ];
    }
}
