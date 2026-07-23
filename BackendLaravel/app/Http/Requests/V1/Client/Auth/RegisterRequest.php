<?php

namespace App\Http\Requests\V1\Client\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'registration_token' => ['required', 'string'],
            'nom' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'ville' => ['required', 'string', 'max:255'],
            'quartier' => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:4096'],   // photo de profil (facultative)
            'fcm_token' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom est obligatoire.',
            'ville.required' => 'La ville est obligatoire.',
            'quartier.required' => 'Le quartier est obligatoire.',
        ];
    }
}
