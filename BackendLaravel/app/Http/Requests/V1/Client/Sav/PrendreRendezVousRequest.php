<?php

namespace App\Http\Requests\V1\Client\Sav;

use Illuminate\Foundation\Http\FormRequest;

class PrendreRendezVousRequest extends FormRequest
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
            'centre_sav_id' => ['required', 'integer', 'exists:centres_sav,id'],
            'intervention_id' => ['nullable', 'integer', 'exists:interventions,id'],
            'creneau' => ['required', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'creneau.after' => 'Le créneau doit être dans le futur.',
        ];
    }
}
