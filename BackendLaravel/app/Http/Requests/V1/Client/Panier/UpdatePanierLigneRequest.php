<?php

namespace App\Http\Requests\V1\Client\Panier;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePanierLigneRequest extends FormRequest
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
            'quantite' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }
}
