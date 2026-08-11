<?php

namespace App\Services;

use App\Enums\RecordStatus;
use App\Models\GstRate;
use App\Models\HsnCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class GstHsnMasterService
{
    private const HSN_REFERENCE_COLUMNS = [
        'items' => 'hsncode', 'purchase_order_items' => 'hsn', 'purchase_items' => 'hsn',
        'sale_order_items' => 'hsn', 'work_order_items' => 'hsn',
    ];

    public function createHsn(array $attributes, Request $request): HsnCode
    {
        return DB::transaction(function () use ($attributes, $request): HsnCode {
            $hsn = new HsnCode();
            $this->fillHsn($hsn, $attributes);
            $hsn->save();
            $this->audit($request, 'created', 'hsn', $hsn, null, $hsn->getAttributes());

            return $hsn;
        });
    }

    public function updateHsn(HsnCode $hsn, array $attributes, Request $request): HsnCode
    {
        return DB::transaction(function () use ($attributes, $request, $hsn): HsnCode {
            $old = $hsn->getAttributes();
            $this->fillHsn($hsn, $attributes);
            $hsn->save();
            $this->audit($request, 'updated', 'hsn', $hsn, $old, $hsn->getAttributes());

            return $hsn;
        });
    }

    public function createRate(array $attributes, Request $request): GstRate
    {
        return DB::transaction(function () use ($attributes, $request): GstRate {
            $rate = new GstRate();
            $this->fillRate($rate, $attributes);
            $rate->save();
            $this->audit($request, 'created', 'gst-rates', $rate, null, $rate->getAttributes());

            return $rate;
        });
    }

    public function updateRate(GstRate $rate, array $attributes, Request $request): GstRate
    {
        return DB::transaction(function () use ($attributes, $request, $rate): GstRate {
            $old = $rate->getAttributes();
            $this->fillRate($rate, $attributes);
            $rate->save();
            $this->audit($request, 'updated', 'gst-rates', $rate, $old, $rate->getAttributes());

            return $rate;
        });
    }

    public function setStatus(HsnCode|GstRate $master, RecordStatus $status, Request $request): void
    {
        $old = $master->getAttributes();
        $master->status = $status->value;
        $master->modified = now();
        $master->save();
        $module = $master instanceof HsnCode ? 'hsn' : 'gst-rates';
        $this->audit($request, 'status_changed', $module, $master, $old, $master->getAttributes());
    }

    public function assertCanDelete(HsnCode|GstRate $master): void
    {
        if ($master instanceof HsnCode && $this->isHsnReferenced($master)) {
            throw ValidationException::withMessages(['hsn_code' => 'Referenced HSN codes cannot be deleted. Deactivate the code instead.']);
        }
        if ($master instanceof GstRate && Schema::hasTable('hsn_codes') && DB::table('hsn_codes')->where('gst_rate_id', $master->getKey())->exists()) {
            throw ValidationException::withMessages(['gst_rate' => 'Referenced GST rates cannot be deleted. Deactivate the rate instead.']);
        }
    }

    public function isHsnReferenced(HsnCode $hsn): bool
    {
        foreach (self::HSN_REFERENCE_COLUMNS as $table => $column) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column) && DB::table($table)->where($column, $hsn->hsn_code)->exists()) {
                return true;
            }
        }

        return false;
    }

    public static function normalizeHsn(?string $value): string
    {
        return preg_replace('/\s+/', ' ', trim((string) $value)) ?? '';
    }

    private function fillHsn(HsnCode $hsn, array $attributes): void
    {
        $hsn->hsn_code = strtoupper(self::normalizeHsn($attributes['hsn_code'] ?? $hsn->hsn_code));
        $hsn->description = filled($attributes['description'] ?? null) ? trim((string) $attributes['description']) : null;
        $hsn->gst_rate_id = $attributes['gst_rate_id'] ?? null;
        $hsn->status = RecordStatus::fromLegacyValue($attributes['status'] ?? 'Active')->value;
        $hsn->created = $hsn->created ?: now();
        $hsn->modified = now();
    }

    private function fillRate(GstRate $rate, array $attributes): void
    {
        $rate->gst_rate = number_format((float) $attributes['gst_rate'], 2, '.', '');
        $rate->description = filled($attributes['description'] ?? null) ? trim((string) $attributes['description']) : null;
        $rate->status = RecordStatus::fromLegacyValue($attributes['status'] ?? 'Active')->value;
        $rate->created = $rate->created ?: now();
        $rate->modified = now();
    }

    private function audit(Request $request, string $event, string $module, HsnCode|GstRate $master, ?array $old, array $new): void
    {
        app(AuditLogger::class)->recordAfterCommit([
            'module' => $module, 'action' => $event === 'created' ? 'create' : 'update', 'event' => $event,
            'description' => ucfirst($module).' master '.$event.'.', 'auditable_type' => $master->getMorphClass(),
            'auditable_id' => $master->getKey(), 'old_values' => $old, 'new_values' => $new, 'request' => $request,
        ]);
    }
}
