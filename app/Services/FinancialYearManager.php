<?php

namespace App\Services;

use App\Models\FinancialYear;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinancialYearManager
{
    public function __construct(private readonly CurrentOrganizationContext $organization) {}

    public function save(array $attributes, ?FinancialYear $financialYear = null): FinancialYear
    {
        $companyId = $this->organization->companyId();
        $code = $this->normalizeCode((string) $attributes['code']);
        $startDate = (string) $attributes['start_date'];
        $endDate = (string) $attributes['end_date'];

        if ($endDate < $startDate) {
            throw ValidationException::withMessages(['end_date' => 'End date must not be before start date.']);
        }

        $expectedStart = '20'.substr($code, 0, 2).'-04-01';
        $expectedEnd = '20'.substr($code, 2, 2).'-03-31';
        if ($startDate !== $expectedStart || $endDate !== $expectedEnd) {
            throw ValidationException::withMessages(['start_date' => 'Dates must match the April-to-March financial year code.']);
        }

        return DB::transaction(function () use ($attributes, $financialYear, $companyId, $code, $startDate, $endDate): FinancialYear {
            $duplicate = FinancialYear::query()->where('company_id', $companyId)->where('code', $code)
                ->when($financialYear, fn ($query) => $query->where('id', '!=', $financialYear->getKey()))->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['code' => 'This financial year already exists for the current company.']);
            }

            $overlap = FinancialYear::query()->where('company_id', $companyId)->where('status', '!=', 'Deleted')
                ->where('start_date', '<=', $endDate)->where('end_date', '>=', $startDate)
                ->when($financialYear, fn ($query) => $query->where('id', '!=', $financialYear->getKey()))->exists();
            if ($overlap) {
                throw ValidationException::withMessages(['start_date' => 'Financial year dates overlap an existing year.']);
            }

            $financialYear ??= new FinancialYear;
            $financialYear->company_id = $companyId;
            $financialYear->code = $code;
            $financialYear->display_name = $this->displayName($startDate, $endDate);
            $financialYear->start_date = $startDate;
            $financialYear->end_date = $endDate;
            $financialYear->status = $attributes['status'] ?? 'Active';
            $financialYear->is_current = false;
            $financialYear->created_by ??= auth('admin')->id();
            $financialYear->modified_by = auth('admin')->id();
            $financialYear->save();

            if (($attributes['is_current'] ?? false) === true) {
                $this->setCurrent($financialYear);
            }

            app(AuditLogger::class)->recordAfterCommit(['module' => 'financial-years', 'action' => $financialYear->wasRecentlyCreated ? 'create' : 'update', 'event' => 'financial_year_saved', 'auditable_type' => $financialYear->getMorphClass(), 'auditable_id' => $financialYear->id, 'description' => 'Financial year saved.', 'new_values' => $financialYear->only(['code', 'start_date', 'end_date', 'status', 'is_current'])]);

            return $financialYear->fresh();
        });
    }

    public function setCurrent(FinancialYear $financialYear): FinancialYear
    {
        $companyId = $this->organization->companyId();
        if ((int) $financialYear->company_id !== $companyId || $financialYear->status !== 'Active') {
            abort(403, 'The financial year is not available in the current company.');
        }

        return DB::transaction(function () use ($financialYear, $companyId): FinancialYear {
            FinancialYear::query()->where('company_id', $companyId)->lockForUpdate()->get();
            FinancialYear::query()->where('company_id', $companyId)->update(['is_current' => false]);
            $financialYear->is_current = true;
            $financialYear->save();

            app(AuditLogger::class)->recordAfterCommit(['module' => 'financial-years', 'action' => 'set-current', 'event' => 'financial_year_current_changed', 'auditable_type' => $financialYear->getMorphClass(), 'auditable_id' => $financialYear->id, 'description' => 'Current financial year changed.', 'new_values' => ['is_current' => true]]);

            return $financialYear->fresh();
        });
    }

    private function normalizeCode(string $code): string
    {
        if (! preg_match('/\A\d{4}\z/', $code)) {
            throw ValidationException::withMessages(['code' => 'Financial year code must use four digits, for example 2627.']);
        }

        return $code;
    }

    private function displayName(string $startDate, string $endDate): string
    {
        return date('Y', strtotime($startDate)).'-'.date('y', strtotime($endDate));
    }
}
