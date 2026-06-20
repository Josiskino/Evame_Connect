<?php

namespace App\Http\Requests\V1\Intervention;

use App\Models\Intervention;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInterventionRequest extends FormRequest
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
            'moto_id' => ['nullable', 'exists:motos,id'],
            'technicien_id' => ['nullable', 'exists:users,id'],
            'probleme' => ['required', 'string'],
            'date_intervention' => ['nullable', 'date'],
            'statut' => ['nullable', Rule::in(Intervention::STATUTS)],
        ];
    }
}
