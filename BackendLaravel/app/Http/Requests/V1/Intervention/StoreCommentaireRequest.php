<?php

namespace App\Http\Requests\V1\Intervention;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentaireRequest extends FormRequest
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
            'contenu' => ['required', 'string'],
        ];
    }
}
