<?php

namespace App\Http\Requests\V1\Leasing;

use Illuminate\Foundation\Http\FormRequest;

class SimulateLeasingRequest extends FormRequest
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
            'duree_jours' => ['required', 'integer', 'min:1'],
            'montant_journalier' => ['required', 'integer', 'min:1'],
        ];
    }
}
