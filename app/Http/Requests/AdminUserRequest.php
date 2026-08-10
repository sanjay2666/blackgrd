<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class AdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'individual_id' => ['nullable', 'integer'],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
            'branch_id' => ['nullable', 'integer'], 'factory_id' => ['nullable', 'integer'], 'department_id' => ['nullable', 'integer'],
            'role_ids' => ['sometimes', 'array'], 'role_ids.*' => ['integer'],
        ];
        if ($this->isMethod('POST')) {
            $rules['password'] = ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()];
        }

        return $rules;
    }
}
