<?php

namespace App\Services;

use App\Models\Individual;
use App\Models\IndividualAddress;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class VendorMasterService
{
    private const REFERENCES = [
        ['purchases', 'vendor_id'], ['purchase_orders', 'vendor_id'], ['warehouse_in_items', 'vendor_id'],
        ['warehouse_item_stocks', 'vendor_id'], ['warehouse_item_stock_files', 'vendor_id'],
        ['stock_mill_dispatches', 'vendor_id'], ['stock_mill_returns', 'vendor_id'], ['work_purchase_requirements', 'vendor_id'],
        ['receive_stock_mill_dispatches', 'vendor_ind_id'],
    ];

    private const ADDRESS_REFERENCES = [['purchase_orders', 'billing_id']];

    public function __construct(private readonly CurrentOrganizationContext $organization, private readonly DatabaseManager $database, private readonly AuditLogger $audit)
    {
    }

    public function save(Individual $vendor, array $a, Request $request): Individual
    {
        return DB::transaction(function () use ($vendor, $a, $request): Individual {
            $this->assertVendor($vendor);
            $company = $this->organization->companyId();
            $code = trim((string) ($a['vendor_code'] ?? '')) ?: null;
            if ($code && Individual::where('company_id', $company)->where('type', 'vendors')->where('status', '!=', 'Deleted')->whereRaw('LOWER(TRIM(vendor_code)) = ?', [strtolower($code)])->when($vendor->exists, fn ($q) => $q->where('id', '!=', $vendor->getKey()))->exists()) {
                throw ValidationException::withMessages(['vendor_code' => 'This Vendor Code already exists.']);
            }
            $gstin = $this->tax($a['gstin'] ?? null, 'gstin', $vendor->gstin);
            $pan = $this->tax($a['pan'] ?? null, 'pan', $vendor->pan);
            $before = $vendor->exists ? $this->snapshot($vendor) : null;
            if ($vendor->exists && $this->referenced($vendor)) {
                foreach (['vendor_code' => $code, 'gstin' => $gstin, 'pan' => $pan] as $field => $value) {
                    if ((string) $vendor->{$field} !== (string) $value) {
                        throw ValidationException::withMessages([$field => "Referenced Vendor {$field} cannot be changed."]);
                    }
                }
            }
            $vendor->fill(['name' => trim((string) $a['name']), 'vendor_code' => $code, 'company_name' => $a['company_name'] ?? null, 'gstin' => $gstin, 'pan' => $pan, 'phone' => $a['phone'] ?? null, 'whatsapp' => $a['whatsapp'] ?? null, 'email' => $a['email'] ?? null, 'status' => $a['status']]);
            $vendor->type = 'vendors';
            $vendor->company_id = $company;
            $vendor->created_by ??= auth('admin')->id();
            $vendor->modified_by = auth('admin')->id();
            $vendor->created_at ??= now();
            $vendor->modified_at = now();
            $vendor->save();
            $this->audit->recordAfterCommit(['module' => 'vendors', 'action' => $before ? 'update' : 'create', 'event' => $before ? 'vendor_updated' : 'vendor_created', 'description' => 'Vendor master saved.', 'auditable_type' => $vendor->getMorphClass(), 'auditable_id' => $vendor->id, 'old_values' => $before, 'new_values' => $this->snapshot($vendor->fresh()), 'request' => $request]);

            return $vendor->fresh(['activeAddresses']);
        });
    }

    public function saveAddress(Individual $vendor, ?IndividualAddress $address, array $a, Request $request): IndividualAddress
    {
        $this->assertVendor($vendor);
        if ($vendor->status !== 'Active') {
            throw ValidationException::withMessages(['vendor' => 'Addresses can only be changed for an active Vendor.']);
        }
        if ($address && ((int) $address->individual_id !== (int) $vendor->id || $address->status === 'Deleted')) {
            throw ValidationException::withMessages(['address' => 'The selected address does not belong to this Vendor.']);
        }
        if (! DB::table('states')->where('id', $a['state_id'])->where('status', 'Active')->exists()) {
            throw ValidationException::withMessages(['state_id' => 'Please select a valid active State.']);
        }
        $before = $address?->getAttributes();
        $address ??= new IndividualAddress();
        $address->fill(['individual_id' => $vendor->id, 'address_type' => $a['address_type'], 'address_1' => trim($a['address_1']), 'address_2' => trim($a['address_2']), 'state_id' => $a['state_id'], 'city' => trim($a['city']), 'zip_code' => trim($a['zip_code']), 'default_address' => (bool) ($a['default_address'] ?? false), 'status' => 'Active']);
        $address->created ??= now();
        $address->created_by ??= auth('admin')->id();
        $address->modified_at = now();
        $address->modified_by = auth('admin')->id();
        if ($address->default_address) {
            IndividualAddress::where('individual_id', $vendor->id)->where('address_type', $a['address_type'])->where('status', 'Active')->when($address->exists, fn ($q) => $q->where('ind_add_id', '!=', $address->id))->update(['default_address' => false]);
        }
        $address->save();
        $this->audit->recordAfterCommit(['module' => 'vendors', 'action' => $before ? 'update' : 'create', 'event' => $before ? 'vendor_address_updated' : 'vendor_address_created', 'description' => 'Vendor address saved.', 'auditable_type' => $address->getMorphClass(), 'auditable_id' => $address->id, 'old_values' => $before, 'new_values' => $address->getAttributes(), 'request' => $request]);

        return $address->fresh();
    }

    public function transition(Individual $vendor, string $status): void
    {
        $this->assertVendor($vendor);
        $before = ['status' => $vendor->status];
        $vendor->update(['status' => $status, 'modified_at' => now(), 'modified_by' => auth('admin')->id()]);
        $this->audit->recordAfterCommit(['module' => 'vendors', 'action' => strtolower($status), 'event' => 'vendor_'.strtolower($status), 'description' => 'Vendor status changed; history remains unchanged.', 'auditable_type' => $vendor->getMorphClass(), 'auditable_id' => $vendor->id, 'old_values' => $before, 'new_values' => ['status' => $status]]);
    }

    public function remove(Individual $vendor, Request $request): void
    {
        $this->assertVendor($vendor);
        if ($this->referenced($vendor)) {
            throw ValidationException::withMessages(['vendor' => 'Referenced Vendors cannot be deleted; deactivate the Vendor instead.']);
        } $before = $this->snapshot($vendor);
        $vendor->update(['status' => 'Deleted', 'deleted_at' => now(), 'modified_at' => now(), 'modified_by' => auth('admin')->id()]);
        $this->audit->recordAfterCommit(['module' => 'vendors', 'action' => 'delete', 'event' => 'vendor_deleted', 'description' => 'Vendor removed.', 'auditable_type' => $vendor->getMorphClass(), 'auditable_id' => $vendor->id, 'old_values' => $before, 'new_values' => $this->snapshot($vendor), 'request' => $request]);
    }

    public function removeAddress(Individual $vendor, IndividualAddress $address, Request $request): void
    {
        $this->assertVendor($vendor);
        if ((int) $address->individual_id !== (int) $vendor->id) {
            throw ValidationException::withMessages(['address' => 'The selected address does not belong to this Vendor.']);
        } if ($this->addressReferenced($address)) {
            throw ValidationException::withMessages(['address' => 'Referenced Vendor addresses cannot be deleted; deactivate the address instead.']);
        } $before = $address->getAttributes();
        $address->update(['status' => 'Deleted', 'modified_at' => now(), 'modified_by' => auth('admin')->id()]);
        $this->audit->recordAfterCommit(['module' => 'vendors', 'action' => 'delete', 'event' => 'vendor_address_deleted', 'description' => 'Vendor address removed.', 'auditable_type' => $address->getMorphClass(), 'auditable_id' => $address->id, 'old_values' => $before, 'new_values' => $address->getAttributes(), 'request' => $request]);
    }

    public function assertActiveVendor(int $id): Individual
    {
        $v = Individual::whereKey($id)->where('company_id', $this->organization->companyId())->where('type', 'vendors')->where('status', 'Active')->first();
        if (! $v) {
            throw ValidationException::withMessages(['vendor_id' => 'Please select a valid active Vendor.']);
        }

        return $v;
    }

    private function assertVendor(Individual $v): void
    {
        if ($v->type !== 'vendors' || (int) $v->company_id !== $this->organization->companyId() || $v->status === 'Deleted') {
            throw ValidationException::withMessages(['vendor' => 'The selected record is not a valid Vendor.']);
        }
    }

    private function referenced(Individual $v): bool
    {
        foreach (self::REFERENCES as [$t, $c]) {
            if (Schema::hasTable($t) && Schema::hasColumn($t, $c) && $this->database->table($t)->where($c, $v->id)->exists()) {
                return true;
            }
        }

        return false;
    }

    private function addressReferenced(IndividualAddress $a): bool
    {
        foreach (self::ADDRESS_REFERENCES as [$t, $c]) {
            if (Schema::hasTable($t) && Schema::hasColumn($t, $c) && $this->database->table($t)->where($c, $a->id)->exists()) {
                return true;
            }
        }

        return false;
    }

    private function tax(?string $value, string $field, ?string $current): ?string
    {
        $v = strtoupper(trim((string) $value));
        if ($v === '') {
            return null;
        } $p = $field === 'gstin' ? '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/' : '/^[A-Z]{5}[0-9]{4}[A-Z]$/';
        if (! preg_match($p, $v) && $v !== (string) $current && ! ($field === 'gstin' && $v === '000000000000000')) {
            throw ValidationException::withMessages([$field => 'Please enter a valid '.strtoupper($field).'.']);
        }

        return $v;
    }

    private function snapshot(Individual $v): array
    {
        return $v->only(['id', 'name', 'vendor_code', 'company_name', 'gstin', 'pan', 'phone', 'whatsapp', 'email', 'type', 'status']);
    }
}
