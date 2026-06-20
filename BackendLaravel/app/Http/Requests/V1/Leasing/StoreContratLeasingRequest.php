<?php

namespace App\Http\Requests\V1\Leasing;

use App\Models\ContratLeasing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContratLeasingRequest extends FormRequest
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
            'vente_id' => ['nullable', 'exists:ventes,id'],
            'date_debut' => ['required', 'date'],
            'duree_jours' => ['required', 'integer', 'min:1'],
            'montant_journalier' => ['required', 'integer', 'min:1'],
            'frequence' => ['required', Rule::in([
                ContratLeasing::FREQUENCE_JOURNALIER,
                ContratLeasing::FREQUENCE_HEBDOMADAIRE,
                ContratLeasing::FREQUENCE_MENSUEL,
            ])],
        ];
    }
}
