<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'pan_no' => $this->normalized('pan_no'),
            'gstin' => $this->normalized('gstin'),
            'pincode' => $this->normalized('pincode'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'legal_name' => ['nullable', 'string', 'max:200'],
            'trade_name' => ['nullable', 'string', 'max:200'],
            'company_code' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'alternate_email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url', 'max:255'],
            'contact_person_name' => ['nullable', 'string', 'max:150'],
            'contact_person_designation' => ['nullable', 'string', 'max:100'],
            'contact_person_mobile' => ['nullable', 'string', 'max:20'],
            'contact_person_email' => ['nullable', 'email', 'max:150'],
            'address_1' => ['nullable', 'string', 'max:555'],
            'address_2' => ['nullable', 'string', 'max:555'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'city_name' => ['nullable', 'string', 'max:100'],
            'district_name' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'regex:/^\d{6}$/'],
            'pan_no' => ['nullable', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'gstin' => ['nullable', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/'],
            'registration_no' => ['nullable', 'string', 'max:100'],
            'tan_no' => ['nullable', 'string', 'max:20'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    private function normalized(string $key): ?string
    {
        $value = $this->input($key);

        return $value === null || trim((string) $value) === '' ? null : strtoupper(trim((string) $value));
    }
}
