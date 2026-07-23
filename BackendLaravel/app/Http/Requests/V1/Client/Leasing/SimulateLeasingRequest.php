<?php

namespace App\Http\Requests\V1\Client\Leasing;

use Illuminate\Foundation\Http\FormRequest;

class SimulateLeasingRequest extends FormRequest
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
        ];
    }
}
