<?php

namespace App\Http\Requests\V1\Client\Panier;

use Illuminate\Foundation\Http\FormRequest;

class AddToPanierRequest extends FormRequest
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
            'piece_id' => ['required', 'integer', 'exists:pieces,id'],
            'quantite' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }
}
