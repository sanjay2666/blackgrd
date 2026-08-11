<?php

namespace App\Services;

use App\Models\Individual;
use App\Models\IndividualAddress;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class TransporterMasterService
{
    private const REFERENCES = [
        ['gate_passes', 'transporter_id'], ['sale_challans', 'transporter_id'], ['dispatches', 'transporter_id'],
        ['purchases', 'transporter_id'], ['purchase_orders', 'transporter_id'], ['stock_mill_dispatches', 'transporter_id'],
        ['receive_stock_mill_dispatches', 'transporter_id'], ['job_works', 'transporter_id'], ['couriers', 'transporter_id'],
    ];

    private const ADDRESS_REFERENCES = [
        ['gate_passes', 'transporter_address_id'], ['sale_challans', 'transporter_address_id'], ['dispatches', 'transporter_address_id'],
    ];

    public function __construct(private readonly CurrentOrganizationContext $organization, private readonly DatabaseManager $database, private readonly AuditLogger $audit)
    {
    }

    public function save(Individual $transporter, array $attributes, Request $request): Individual
    {
        return DB::transaction(function () use ($transporter, $attributes, $request): Individual {
            $this->assertTransporter($transporter);
            $code = trim((string) ($attributes['transporter_code'] ?? '')) ?: null;
            if ($code && Individual::where('type', 'transport')->where('status', '!=', 'Deleted')->whereRaw('LOWER(TRIM(transporter_code)) = ?', [strtolower($code)])->when($transporter->exists, fn ($query) => $query->where('id', '!=', $transporter->id))->exists()) {
                throw ValidationException::withMessages(['transporter_code' => 'This Transporter Code already exists.']);
            }

            $gstin = $this->normalizeTax($attributes['gstin'] ?? null, 'gstin', $transporter->gstin);
            $pan = $this->normalizeTax($attributes['pan'] ?? null, 'pan', $transporter->pan);
            $before = $transporter->exists ? $this->snapshot($transporter) : null;
            if ($transporter->exists && $this->isReferenced($transporter)) {
                foreach (['transporter_code' => $code, 'gstin' => $gstin, 'pan' => $pan] as $field => $value) {
                    if ((string) $transporter->{$field} !== (string) $value) {
                        throw ValidationException::withMessages([$field => "Referenced Transporter {$field} cannot be changed."]);
                    }
                }
            }

            $transporter->fill(['name' => trim((string) $attributes['name']), 'transporter_code' => $code, 'company_name' => $attributes['company_name'] ?? null, 'gstin' => $gstin, 'pan' => $pan, 'phone' => $attributes['phone'] ?? null, 'whatsapp' => $attributes['whatsapp'] ?? null, 'email' => $attributes['email'] ?? null, 'status' => $attributes['status']]);
            $transporter->type = 'transport';
            $transporter->company_id = $this->organization->companyId();
            $transporter->created_by ??= auth('admin')->id();
            $transporter->modified_by = auth('admin')->id();
            $transporter->created_at ??= now();
            $transporter->modified_at = now();
            $transporter->save();
            $this->audit->recordAfterCommit(['module' => 'transporters', 'action' => $before ? 'update' : 'create', 'event' => $before ? 'transporter_updated' : 'transporter_created', 'description' => 'Transporter master saved.', 'auditable_type' => $transporter->getMorphClass(), 'auditable_id' => $transporter->id, 'old_values' => $before, 'new_values' => $this->snapshot($transporter->fresh()), 'request' => $request]);

            return $transporter->fresh(['activeAddresses']);
        });
    }

    public function saveAddress(Individual $transporter, ?IndividualAddress $address, array $attributes, Request $request): IndividualAddress
    {
        $this->assertTransporter($transporter);
        if ($transporter->status !== 'Active') {
            throw ValidationException::withMessages(['transporter' => 'Addresses can only be changed for an active Transporter.']);
        }
        if ($address && ((int) $address->individual_id !== (int) $transporter->id || $address->status === 'Deleted')) {
            throw ValidationException::withMessages(['address' => 'The selected address does not belong to this Transporter.']);
        }
        if (! DB::table('states')->where('id', $attributes['state_id'])->where('status', 'Active')->exists()) {
            throw ValidationException::withMessages(['state_id' => 'Please select a valid active State.']);
        }

        $before = $address?->getAttributes();
        $address ??= new IndividualAddress();
        $address->fill(['individual_id' => $transporter->id, 'address_type' => $attributes['address_type'], 'address_1' => trim($attributes['address_1']), 'address_2' => trim($attributes['address_2']), 'state_id' => $attributes['state_id'], 'city' => trim($attributes['city']), 'zip_code' => trim($attributes['zip_code']), 'default_address' => (bool) ($attributes['default_address'] ?? false), 'status' => 'Active']);
        $address->created ??= now();
        $address->created_by ??= auth('admin')->id();
        $address->modified_at = now();
        $address->modified_by = auth('admin')->id();
        if ($address->default_address) {
            IndividualAddress::where('individual_id', $transporter->id)->where('address_type', $attributes['address_type'])->where('status', 'Active')->when($address->exists, fn ($query) => $query->where('ind_add_id', '!=', $address->id))->update(['default_address' => false]);
        }
        $address->save();
        $this->audit->recordAfterCommit(['module' => 'transporters', 'action' => $before ? 'update' : 'create', 'event' => $before ? 'transporter_address_updated' : 'transporter_address_created', 'description' => 'Transporter address saved.', 'auditable_type' => $address->getMorphClass(), 'auditable_id' => $address->id, 'old_values' => $before, 'new_values' => $address->getAttributes(), 'request' => $request]);

        return $address->fresh();
    }

    public function transition(Individual $transporter, string $status): void
    {
        $this->assertTransporter($transporter);
        $before = ['status' => $transporter->status];
        $transporter->update(['status' => $status, 'modified_at' => now(), 'modified_by' => auth('admin')->id()]);
        $this->audit->recordAfterCommit(['module' => 'transporters', 'action' => strtolower($status), 'event' => 'transporter_'.strtolower($status), 'description' => 'Transporter status changed; historical documents remain unchanged.', 'auditable_type' => $transporter->getMorphClass(), 'auditable_id' => $transporter->id, 'old_values' => $before, 'new_values' => ['status' => $status]]);
    }

    public function remove(Individual $transporter, Request $request): void
    {
        $this->assertTransporter($transporter);
        if ($this->isReferenced($transporter)) {
            throw ValidationException::withMessages(['transporter' => 'Referenced Transporters cannot be deleted; deactivate the Transporter instead.']);
        }
        $before = $this->snapshot($transporter);
        $transporter->update(['status' => 'Deleted', 'deleted_at' => now(), 'modified_at' => now(), 'modified_by' => auth('admin')->id()]);
        $this->audit->recordAfterCommit(['module' => 'transporters', 'action' => 'delete', 'event' => 'transporter_deleted', 'description' => 'Transporter removed.', 'auditable_type' => $transporter->getMorphClass(), 'auditable_id' => $transporter->id, 'old_values' => $before, 'new_values' => $this->snapshot($transporter), 'request' => $request]);
    }

    public function removeAddress(Individual $transporter, IndividualAddress $address, Request $request): void
    {
        $this->assertTransporter($transporter);
        if ((int) $address->individual_id !== (int) $transporter->id) {
            throw ValidationException::withMessages(['address' => 'The selected address does not belong to this Transporter.']);
        }
        if ($this->isAddressReferenced($address)) {
            throw ValidationException::withMessages(['address' => 'Referenced Transporter addresses cannot be deleted; deactivate the address instead.']);
        }
        $before = $address->getAttributes();
        $address->update(['status' => 'Deleted', 'modified_at' => now(), 'modified_by' => auth('admin')->id()]);
        $this->audit->recordAfterCommit(['module' => 'transporters', 'action' => 'delete', 'event' => 'transporter_address_deleted', 'description' => 'Transporter address removed.', 'auditable_type' => $address->getMorphClass(), 'auditable_id' => $address->id, 'old_values' => $before, 'new_values' => $address->getAttributes(), 'request' => $request]);
    }

    public function assertActiveTransporter(int $id): Individual
    {
        $transporter = Individual::whereKey($id)->where('type', 'transport')->where('status', 'Active')->first();
        if (! $transporter) {
            throw ValidationException::withMessages(['transporter_id' => 'Please select a valid active Transporter.']);
        }
        return $transporter;
    }

    private function assertTransporter(Individual $transporter): void
    {
        if ($transporter->type !== 'transport' || (int) $transporter->company_id !== $this->organization->companyId() || $transporter->status === 'Deleted') {
            throw ValidationException::withMessages(['transporter' => 'The selected record is not a valid Transporter.']);
        }
    }

    private function isReferenced(Individual $transporter): bool
    {
        foreach (self::REFERENCES as [$table, $column]) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column) && $this->database->table($table)->where($column, $transporter->id)->exists()) {
                return true;
            }
        }
        return false;
    }

    private function isAddressReferenced(IndividualAddress $address): bool
    {
        foreach (self::ADDRESS_REFERENCES as [$table, $column]) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column) && $this->database->table($table)->where($column, $address->id)->exists()) {
                return true;
            }
        }
        return false;
    }

    private function normalizeTax(?string $value, string $field, ?string $current): ?string
    {
        $normalized = strtoupper(trim((string) $value));
        if ($normalized === '') {
            return null;
        }
        $pattern = $field === 'gstin' ? '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/' : '/^[A-Z]{5}[0-9]{4}[A-Z]$/';
        if (! preg_match($pattern, $normalized) && $normalized !== (string) $current && ! ($field === 'gstin' && $normalized === '000000000000000')) {
            throw ValidationException::withMessages([$field => 'Please enter a valid '.strtoupper($field).'.']);
        }
        return $normalized;
    }

    private function snapshot(Individual $transporter): array
    {
        return $transporter->only(['id', 'name', 'transporter_code', 'company_name', 'gstin', 'pan', 'phone', 'whatsapp', 'email', 'type', 'status']);
    }
}
