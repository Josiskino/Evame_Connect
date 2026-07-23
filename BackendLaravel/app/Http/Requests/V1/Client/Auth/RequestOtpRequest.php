<?php

namespace App\Http\Requests\V1\Client\Auth;

use App\Support\PhoneNormalizer;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class RequestOtpRequest extends FormRequest
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
            'locale' => ['sometimes', 'in:fr,en'],
        ];
    }

    public function messages(): array
    {
        return [
            'telephone.required' => 'Le numéro de téléphone est obligatoire.',
        ];
    }
}
