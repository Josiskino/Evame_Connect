<?php

namespace App\Http\Requests\V1\Leasing;

use Illuminate\Foundation\Http\FormRequest;

class StorePaiementRequest extends FormRequest
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
            'montant' => ['required', 'integer', 'min:1'],
            'date_paiement' => ['nullable', 'date'],
        ];
    }
}
