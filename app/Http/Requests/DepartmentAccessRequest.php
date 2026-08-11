<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DepartmentAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return ['department_ids' => ['nullable', 'array'], 'department_ids.*' => ['integer', 'distinct']];
    }
}
