<?php

namespace Tests\Unit\Masters;

use App\Support\AdminNavigation;
use App\Support\PermissionRegistry;
use Tests\TestCase;

final class YarnMasterRecipeContractTest extends TestCase
{
    public function test_yarn_recipe_reuses_item_identity_and_process_specific_requirement_table(): void
    {
        $service = file_get_contents(base_path('app/Services/YarnRecipeService.php'));
        $model = file_get_contents(base_path('app/Models/ItemYarnRequirement.php'));
        $workOrder = file_get_contents(base_path('app/Http/Controllers/WorkOrderController.php'));

        $this->assertStringContainsString("whereRaw('LOWER(item_type_name) = ?', ['yarn'])", $service);
        $this->assertStringContainsString("where('item_type_id', \$this->yarnItemType()->getKey())", $service);
        $this->assertStringContainsString("where('process_id', \$process->getKey())", $service);
        $this->assertStringContainsString("where('status', RecordStatus::Active->value)", $service);
        $this->assertStringContainsString("where('unit', \$unit->unit_type_name)", $service);
        $this->assertStringContainsString('function process()', $model);
        $this->assertStringContainsString("where('item_id', \$itemId)->where('process_id', \$processId)", $workOrder);
        $this->assertStringNotContainsString('yarn_recipe', $model);
    }

    public function test_yarn_recipe_uses_canonical_rbac_navigation_and_audit(): void
    {
        $service = file_get_contents(base_path('app/Services/YarnRecipeService.php'));
        $navigation = collect(AdminNavigation::groups())->flatMap(fn (array $group): array => $group['items']);

        $this->assertStringContainsString("'module' => 'masters'", $service);
        $this->assertStringContainsString('recordAfterCommit', $service);
        $this->assertTrue($navigation->contains(fn (array $item): bool => $item['route'] === 'admin.item-yarn-requirements.index' && $item['permission'] === 'masters.view'));
        $this->assertContains('masters.manage-yarn', PermissionRegistry::companyAdminAssignable());
        $this->assertNotContains('masters.manage-yarn', PermissionRegistry::frontendAssignable());
    }
}
