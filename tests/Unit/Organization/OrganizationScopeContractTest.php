<?php

namespace Tests\Unit\Organization;

use Tests\TestCase;

class OrganizationScopeContractTest extends TestCase
{
    public function test_organization_schema_and_context_contract_are_present(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_08_10_000001_create_organization_scope_tables.php'));
        $context = file_get_contents(base_path('app/Services/CurrentOrganizationContext.php'));
        $routes = file_get_contents(base_path('routes/web.php'));

        $this->assertStringContainsString("Schema::create('branches'", $migration);
        $this->assertStringContainsString("Schema::create('factories'", $migration);
        $this->assertStringContainsString("Schema::create('user_organization_access'", $migration);
        $this->assertStringContainsString("\$request->session()->get('organization.company_id')", $context);
        $this->assertStringContainsString("where('company_id', \$requestedCompany)", $context);
        $this->assertStringContainsString('organization.switch', $routes);
    }

    public function test_active_print_paths_do_not_use_company_one(): void
    {
        foreach (['app/Http/Controllers/WorkOrderController.php', 'app/Http/Controllers/WorkProcessRequirementController.php'] as $file) {
            $source = file_get_contents(base_path($file));
            $this->assertStringNotContainsString('Company::find(1)', $source);
        }
    }
}
