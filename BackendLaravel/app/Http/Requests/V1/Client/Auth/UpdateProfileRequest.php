<?php

namespace App\Http\Requests\V1\Client\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
            'nom' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'ville' => ['sometimes', 'required', 'string', 'max:255'],
            'quartier' => ['sometimes', 'required', 'string', 'max:255'],
            'photo' => ['sometimes', 'nullable', 'image', 'max:4096'],
        ];
    }
}
