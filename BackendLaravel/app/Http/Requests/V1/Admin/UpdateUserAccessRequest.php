<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation commune pour accorder/révoquer une permission à un utilisateur.
 */
class UpdateUserAccessRequest extends FormRequest
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
            'permission' => ['required', 'string', 'exists:permissions,name'],
        ];
    }
}
