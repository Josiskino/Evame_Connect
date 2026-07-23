<?php

namespace App\Http\Requests\V1\Client\Auth;

use App\Support\PhoneNormalizer;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
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
            'telephone' => [
                'required', 'string',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (! PhoneNormalizer::isValid((string) $value)) {
                        $fail('Le numéro de téléphone est invalide.');
                    }
                },
            ],
            'code' => ['required', 'string', 'digits:6'],
            'fcm_token' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code de vérification est obligatoire.',
            'code.digits' => 'Le code doit comporter 6 chiffres.',
        ];
    }
}
