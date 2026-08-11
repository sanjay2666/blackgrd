<?php

namespace App\Services;

use App\Models\DocumentSetting;
use Illuminate\Validation\ValidationException;

final class DocumentSettingsService
{
    private const DOCUMENTS = ['sale_order', 'purchase_order', 'work_order', 'gate_pass', 'job_work_dispatch', 'job_work_receive'];

    private const DEFAULTS = [
        'show_logo' => true, 'show_company_address' => true, 'show_company_tax_identity' => true,
        'document_title' => null, 'footer_text' => null, 'terms_text' => null,
        'signatory_label' => 'Authorized Signatory', 'copy_label_primary' => 'Original Copy',
        'copy_label_secondary' => null, 'copy_label_tertiary' => null,
    ];

    public function __construct(private readonly CurrentOrganizationContext $organization, private readonly AuditLogger $audit)
    {
    }

    /** @return array<string, mixed> */
    public function for(string $documentKey): array
    {
        $this->assertDocument($documentKey);
        $row = DocumentSetting::query()->where('company_id', $this->organization->companyId())->where('document_key', $documentKey)->first();
        return array_merge(self::DEFAULTS, $row?->only(array_keys(self::DEFAULTS)) ?? []);
    }

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        return collect(self::DOCUMENTS)->mapWithKeys(fn (string $key): array => [$key => $this->for($key)])->all();
    }

    /** @param array<string, mixed> $values */
    public function save(string $documentKey, array $values): DocumentSetting
    {
        $this->assertDocument($documentKey);
        $validated = $this->validate($values);
        $companyId = $this->organization->companyId();
        $setting = DocumentSetting::query()->firstOrNew(['company_id' => $companyId, 'document_key' => $documentKey]);
        $before = $setting->exists ? $setting->only(array_keys(self::DEFAULTS)) : self::DEFAULTS;
        $setting->fill($validated);
        $setting->created_by ??= auth('admin')->id();
        $setting->modified_by = auth('admin')->id();
        $setting->created_at ??= now();
        $setting->modified_at = now();
        $setting->save();
        $after = $setting->fresh()->only(array_keys(self::DEFAULTS));
        if ($before !== $after) {
            $this->audit->recordAfterCommit(['module' => 'document-settings', 'action' => 'update', 'event' => 'document_settings_updated', 'description' => 'Document presentation settings updated.', 'auditable_type' => $setting->getMorphClass(), 'auditable_id' => $setting->id, 'old_values' => $before, 'new_values' => $after, 'changed_fields' => array_keys(array_diff_assoc($after, $before))]);
        }
        return $setting->fresh();
    }

    /** @param array<string, mixed> $values @return array<string, mixed> */
    private function validate(array $values): array
    {
        $allowed = array_keys(self::DEFAULTS);
        $unsupported = array_diff(array_keys($values), $allowed);
        if ($unsupported !== []) {
            throw ValidationException::withMessages(['settings' => 'Unsupported document setting: '.reset($unsupported)]);
        }
        foreach (['show_logo', 'show_company_address', 'show_company_tax_identity'] as $key) {
            $values[$key] = (bool) ($values[$key] ?? false);
        }
        foreach (['document_title', 'footer_text', 'terms_text', 'signatory_label', 'copy_label_primary', 'copy_label_secondary', 'copy_label_tertiary'] as $key) {
            if (array_key_exists($key, $values)) {
                $values[$key] = trim((string) $values[$key]) ?: null;
            }
        }
        if (($values['footer_text'] ?? '') !== '' && preg_match('/<\/?(script|iframe|object|style|php)\b/i', (string) $values['footer_text'])) {
            throw ValidationException::withMessages(['footer_text' => 'Footer text contains unsupported markup.']);
        }
        if (($values['terms_text'] ?? '') !== '' && preg_match('/<\/?(script|iframe|object|style|php)\b/i', (string) $values['terms_text'])) {
            throw ValidationException::withMessages(['terms_text' => 'Terms text contains unsupported markup.']);
        }
        return array_intersect_key($values, array_flip($allowed));
    }

    private function assertDocument(string $documentKey): void
    {
        if (! in_array($documentKey, self::DOCUMENTS, true)) {
            throw ValidationException::withMessages(['document_key' => 'Unsupported document type.']);
        }
    }
}
