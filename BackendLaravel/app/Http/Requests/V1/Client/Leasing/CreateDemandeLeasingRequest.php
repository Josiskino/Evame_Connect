<?php

namespace App\Http\Requests\V1\Client\Leasing;

use App\Models\DemandeLeasing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateDemandeLeasingRequest extends FormRequest
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
            'moto_id' => ['required', 'integer', 'exists:motos,id'],
            'frequence' => ['sometimes', Rule::in(DemandeLeasing::FREQUENCES)],
        ];
    }
}
