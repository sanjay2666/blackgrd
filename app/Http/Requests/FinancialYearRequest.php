<?php

namespace App\Http\Requests;

use App\Rules\RecordStatusRule;
use Illuminate\Foundation\Http\FormRequest;

class FinancialYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'regex:/^\d{4}$/'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', new RecordStatusRule],
        ];
    }
}
