<?php

namespace App\Http\Requests\V1\Client\Panne;

use App\Models\Intervention;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeclarerPanneRequest extends FormRequest
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
            'categorie' => ['required', Rule::in(Intervention::CATEGORIES)],
            'urgence' => ['required', Rule::in(Intervention::URGENCES)],
            'description' => ['required', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'La description de la panne est obligatoire.',
        ];
    }
}
