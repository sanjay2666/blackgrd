<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DocumentSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentSettingsController extends Controller
{
    public function edit(DocumentSettingsService $settings): View
    {
        return view('admin.document-settings.edit', ['settings' => $settings->all()]);
    }

    public function update(Request $request, DocumentSettingsService $settings): RedirectResponse
    {
        foreach (array_keys($settings->all()) as $documentKey) {
            $settings->save($documentKey, $this->validated($request, $documentKey));
        }
        return back()->with('message', 'Document settings updated successfully.')->with('messageClass', 'successClass');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, string $key): array
    {
        $prefix = 'settings.'.$key.'.';
        $data = $request->validate([
            $prefix.'show_logo' => ['nullable', 'boolean'], $prefix.'show_company_address' => ['nullable', 'boolean'], $prefix.'show_company_tax_identity' => ['nullable', 'boolean'],
            $prefix.'document_title' => ['nullable', 'string', 'max:150'], $prefix.'footer_text' => ['nullable', 'string', 'max:5000'], $prefix.'terms_text' => ['nullable', 'string', 'max:5000'],
            $prefix.'signatory_label' => ['nullable', 'string', 'max:100'], $prefix.'copy_label_primary' => ['nullable', 'string', 'max:100'], $prefix.'copy_label_secondary' => ['nullable', 'string', 'max:100'], $prefix.'copy_label_tertiary' => ['nullable', 'string', 'max:100'],
        ]);
        $result = [];
        foreach (array_keys($settings->for($key)) as $field) {
            $result[$field] = $data[$prefix.$field] ?? null;
        }
        return $result;
    }
}
