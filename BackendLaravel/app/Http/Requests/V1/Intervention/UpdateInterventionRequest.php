<?php

namespace App\Http\Requests\V1\Intervention;

use App\Models\Intervention;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInterventionRequest extends FormRequest
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
            'statut' => ['sometimes', 'required', Rule::in(Intervention::STATUTS)],
            'technicien_id' => ['sometimes', 'nullable', 'exists:users,id'],
            'probleme' => ['sometimes', 'required', 'string'],
        ];
    }
}
