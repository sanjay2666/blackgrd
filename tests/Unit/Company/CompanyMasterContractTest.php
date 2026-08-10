<?php

namespace Tests\Unit\Company;

use Tests\TestCase;

final class CompanyMasterContractTest extends TestCase
{
    public function test_company_master_is_profile_only_and_uses_existing_canonical_foundation(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $controller = file_get_contents(base_path('app/Http/Controllers/Admin/CompanyController.php'));
        $profile = file_get_contents(base_path('app/Services/CompanyProfileService.php'));
        $request = file_get_contents(base_path('app/Http/Requests/CompanyProfileRequest.php'));
        $navigation = file_get_contents(base_path('app/Support/AdminNavigation.php'));

        $this->assertStringContainsString("Route::get('/companies'", $routes);
        $this->assertStringContainsString("Route::put('/companies'", $routes);
        $this->assertStringNotContainsString("companies.create", $routes);
        $this->assertStringNotContainsString("companies.store", $routes);
        $this->assertStringNotContainsString("companies.destroy", $routes);
        $this->assertStringContainsString('CurrentOrganizationContext', $profile);
        $this->assertStringContainsString('recordAfterCommit', $profile);
        $this->assertStringContainsString("'gstin'", $request);
        $this->assertStringContainsString("'pan_no'", $request);
        $this->assertStringContainsString('Company Profile', $navigation);
        $this->assertStringNotContainsString('Add Company', file_get_contents(base_path('resources/views/admin/companies/index.blade.php')));
    }
}
