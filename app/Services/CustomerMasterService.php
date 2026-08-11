<?php

namespace App\Services;

use App\Enums\RecordStatus;
use App\Models\Individual;
use App\Models\IndividualAddress;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class CustomerMasterService
{
    /** @var list<array{table:string,column:string}> */
    private const CUSTOMER_REFERENCES = [
        ['table' => 'sale_orders', 'column' => 'customer_id'], ['table' => 'work_order_items', 'column' => 'customer_id'],
        ['table' => 'couriers', 'column' => 'cus_id'],
    ];

    /** @var list<array{table:string,column:string}> */
    private const ADDRESS_REFERENCES = [
        ['table' => 'sale_orders', 'column' => 'billing_id'], ['table' => 'sale_orders', 'column' => 'shipping_id'],
    ];

    public function __construct(private readonly CurrentOrganizationContext $organization, private readonly DatabaseManager $database, private readonly AuditLogger $audit)
    {
    }

    public function save(Individual $customer, array $attributes, Request $request): Individual
    {
        return DB::transaction(function () use ($customer, $attributes, $request): Individual {
            $companyId = $this->organization->companyId();
            $this->assertCustomerIdentity($customer, $companyId);
            $code = trim((string) ($attributes['customer_code'] ?? '')) ?: null;
            if ($code !== null && Individual::query()->where('company_id', $companyId)->where('type', 'customers')->where('status', '!=', RecordStatus::Deleted->value)->whereRaw('LOWER(TRIM(customer_code)) = ?', [strtolower($code)])->when($customer->exists, fn ($q) => $q->where('id', '!=', $customer->getKey()))->exists()) {
                throw ValidationException::withMessages(['customer_code' => 'This Customer Code already exists.']);
            }
            $gstin = $this->normalizeTax($attributes['gstin'] ?? null, 'gstin', $customer->gstin ?? null);
            $pan = $this->normalizeTax($attributes['pan'] ?? null, 'pan', $customer->pan ?? null);
            $before = $customer->exists ? $this->snapshot($customer) : null;
            if ($customer->exists && $this->isReferenced($customer)) {
                foreach (['customer_code' => 'Customer Code', 'gstin' => 'GSTIN', 'pan' => 'PAN'] as $field => $label) {
                    if ((string) ($customer->{$field} ?? '') !== (string) ($field === 'gstin' ? $gstin : ($field === 'pan' ? $pan : $code))) {
                        throw ValidationException::withMessages([$field => "Referenced Customer {$label} cannot be changed because it may alter historical meaning."]);
                    }
                }
            }
            $customer->fill(['name' => trim((string) $attributes['name']), 'customer_code' => $code, 'company_name' => $attributes['company_name'] ?? null, 'gstin' => $gstin, 'pan' => $pan, 'phone' => $attributes['phone'] ?? null, 'whatsapp' => $attributes['whatsapp'] ?? null, 'email' => $attributes['email'] ?? null, 'status' => $attributes['status']]);
            $customer->type = 'customers';
            $customer->company_id = $companyId;
            $customer->created_by = $customer->created_by ?: auth('admin')->id();
            $customer->modified_by = auth('admin')->id();
            $customer->created_at = $customer->created_at ?: now();
            $customer->modified_at = now();
            $customer->save();
            $this->audit->recordAfterCommit(['module' => 'customers', 'action' => $before ? 'update' : 'create', 'event' => $before ? 'customer_updated' : 'customer_created', 'description' => 'Customer master saved.', 'auditable_type' => $customer->getMorphClass(), 'auditable_id' => $customer->getKey(), 'old_values' => $before, 'new_values' => $this->snapshot($customer->fresh()), 'request' => $request]);

            return $customer->fresh(['activeAddresses']);
        });
    }

    public function saveAddress(Individual $customer, ?IndividualAddress $address, array $attributes, Request $request): IndividualAddress
    {
        return DB::transaction(function () use ($customer, $address, $attributes, $request): IndividualAddress {
            $this->assertCustomerIdentity($customer, $this->organization->companyId());
            if ($customer->status !== RecordStatus::Active->value) {
                throw ValidationException::withMessages(['customer' => 'Addresses can only be changed for an active Customer.']);
            }
            if ($address && ((int) $address->individual_id !== (int) $customer->getKey() || $address->status === RecordStatus::Deleted->value)) {
                throw ValidationException::withMessages(['address' => 'The selected address does not belong to this Customer.']);
            }
            $state = DB::table('states')->where('id', $attributes['state_id'])->where('status', 'Active')->exists();
            if (! $state) {
                throw ValidationException::withMessages(['state_id' => 'Please select a valid active State.']);
            }
            $before = $address?->getAttributes();
            $address ??= new IndividualAddress();
            $address->fill(['individual_id' => $customer->getKey(), 'address_type' => $attributes['address_type'], 'address_1' => trim((string) $attributes['address_1']), 'address_2' => trim((string) $attributes['address_2']), 'state_id' => $attributes['state_id'], 'city' => trim((string) $attributes['city']), 'zip_code' => trim((string) $attributes['zip_code']), 'default_address' => (bool) ($attributes['default_address'] ?? false), 'status' => RecordStatus::Active->value]);
            $address->created = $address->created ?: now();
            $address->created_by = $address->created_by ?: auth('admin')->id();
            $address->modified_at = now();
            $address->modified_by = auth('admin')->id();
            if ($address->default_address) {
                IndividualAddress::query()->where('individual_id', $customer->getKey())->where('address_type', $attributes['address_type'])->where('status', RecordStatus::Active->value)->when($address->exists, fn ($q) => $q->where('ind_add_id', '!=', $address->getKey()))->update(['default_address' => false]);
            }
            $address->save();
            $this->audit->recordAfterCommit(['module' => 'customers', 'action' => $before ? 'update' : 'create', 'event' => $before ? 'customer_address_updated' : 'customer_address_created', 'description' => 'Customer address saved.', 'auditable_type' => $address->getMorphClass(), 'auditable_id' => $address->getKey(), 'old_values' => $before, 'new_values' => $address->getAttributes(), 'request' => $request]);

            return $address->fresh();
        });
    }

    public function transition(Individual $customer, string $status): void
    {
        $this->assertCustomerIdentity($customer, $this->organization->companyId());
        $before = ['status' => $customer->getRawOriginal('status')];
        $customer->status = $status;
        $customer->modified_at = now();
        $customer->modified_by = auth('admin')->id();
        $customer->save();
        $this->audit->recordAfterCommit(['module' => 'customers', 'action' => strtolower($status), 'event' => 'customer_'.strtolower($status), 'description' => 'Customer status changed; historical transactions remain unchanged.', 'auditable_type' => $customer->getMorphClass(), 'auditable_id' => $customer->getKey(), 'old_values' => $before, 'new_values' => ['status' => $status]]);
    }

    public function remove(Individual $customer, Request $request): void
    {
        $this->assertCustomerIdentity($customer, $this->organization->companyId());
        if ($this->isReferenced($customer)) {
            throw ValidationException::withMessages(['customer' => 'Referenced Customers cannot be deleted; deactivate the Customer instead.']);
        }
        $before = $this->snapshot($customer);
        $customer->status = RecordStatus::Deleted->value;
        $customer->deleted_at = now();
        $customer->modified_at = now();
        $customer->modified_by = auth('admin')->id();
        $customer->save();
        $this->audit->recordAfterCommit(['module' => 'customers', 'action' => 'delete', 'event' => 'customer_deleted', 'description' => 'Customer removed.', 'auditable_type' => $customer->getMorphClass(), 'auditable_id' => $customer->getKey(), 'old_values' => $before, 'new_values' => $this->snapshot($customer), 'request' => $request]);
    }

    public function removeAddress(Individual $customer, IndividualAddress $address, Request $request): void
    {
        $this->assertCustomerIdentity($customer, $this->organization->companyId());
        if ((int) $address->individual_id !== (int) $customer->getKey() || $address->status === RecordStatus::Deleted->value) {
            throw ValidationException::withMessages(['address' => 'The selected address does not belong to this Customer.']);
        }
        if ($this->isAddressReferenced($address)) {
            throw ValidationException::withMessages(['address' => 'Referenced Customer addresses cannot be deleted; deactivate the address instead.']);
        }
        $before = $address->getAttributes();
        $address->status = RecordStatus::Deleted->value;
        $address->modified_at = now();
        $address->modified_by = auth('admin')->id();
        $address->save();
        $this->audit->recordAfterCommit(['module' => 'customers', 'action' => 'delete', 'event' => 'customer_address_deleted', 'description' => 'Customer address removed.', 'auditable_type' => $address->getMorphClass(), 'auditable_id' => $address->getKey(), 'old_values' => $before, 'new_values' => $address->getAttributes(), 'request' => $request]);
    }

    public function assertActiveCustomer(int $id): Individual
    {
        $customer = Individual::query()->whereKey($id)->where('company_id', $this->organization->companyId())->where('type', 'customers')->where('status', RecordStatus::Active->value)->first();
        if (! $customer) {
            throw ValidationException::withMessages(['customer_id' => 'Please select a valid active Customer.']);
        }

        return $customer;
    }

    public function assertAddressBelongs(?int $addressId, int $customerId, string $field = 'address_id'): void
    {
        if ($addressId === null || $addressId === 0) {
            return;
        }
        if (! IndividualAddress::query()->whereKey($addressId)->where('individual_id', $customerId)->where('status', RecordStatus::Active->value)->exists()) {
            throw ValidationException::withMessages([$field => 'The selected address does not belong to this Customer.']);
        }
    }

    public function isReferenced(Individual $customer): bool
    {
        foreach (self::CUSTOMER_REFERENCES as $reference) {
            if (Schema::hasTable($reference['table']) && Schema::hasColumn($reference['table'], $reference['column']) && $this->database->table($reference['table'])->where($reference['column'], $customer->getKey())->exists()) {
                return true;
            }
        }

        return false;
    }

    private function isAddressReferenced(IndividualAddress $address): bool
    {
        foreach (self::ADDRESS_REFERENCES as $reference) {
            if (Schema::hasTable($reference['table']) && Schema::hasColumn($reference['table'], $reference['column']) && $this->database->table($reference['table'])->where($reference['column'], $address->getKey())->exists()) {
                return true;
            }
        }

        return false;
    }

    private function assertCustomerIdentity(Individual $customer, int $companyId): void
    {
        if ($customer->type !== 'customers' || (int) $customer->company_id !== $companyId || $customer->status === RecordStatus::Deleted->value) {
            throw ValidationException::withMessages(['customer' => 'The selected record is not a valid Customer.']);
        }
    }

    private function normalizeTax(?string $value, string $field, ?string $current): ?string
    {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }
        $normalized = strtoupper($normalized);
        $pattern = $field === 'gstin' ? '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/' : '/^[A-Z]{5}[0-9]{4}[A-Z]$/';
        if (! preg_match($pattern, $normalized) && $normalized !== (string) $current && ! ($field === 'gstin' && $normalized === '000000000000000')) {
            throw ValidationException::withMessages([$field => 'Please enter a valid '.strtoupper($field).'.']);
        }

        return $normalized;
    }

    /** @return array<string, mixed> */
    private function snapshot(Individual $customer): array
    {
        return $customer->only(['id', 'name', 'customer_code', 'company_name', 'gstin', 'pan', 'phone', 'whatsapp', 'email', 'type', 'status']);
    }
}
