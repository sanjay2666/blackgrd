<?php

namespace App\Services;

use App\Exceptions\MissingCurrentFinancialYear;
use App\Models\FinancialYear;

class FinancialYearResolver
{
    public function __construct(private readonly CurrentOrganizationContext $organization) {}

    public function current(?int $companyId = null): FinancialYear
    {
        $companyId ??= $this->organization->companyId();
        $financialYear = FinancialYear::query()
            ->where('company_id', $companyId)
            ->where('is_current', true)
            ->where('status', 'Active')
            ->first();

        if ($financialYear === null) {
            throw new MissingCurrentFinancialYear("No active current financial year is configured for company [{$companyId}].");
        }

        return $financialYear;
    }

    public function code(?int $companyId = null): string
    {
        return $this->current($companyId)->code;
    }
}
