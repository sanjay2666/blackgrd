<?php

namespace Tests\Unit\SalesChallan;

use PHPUnit\Framework\TestCase;

class SalesChallanLiveMigrationCommandTest extends TestCase
{
    public function test_live_apply_is_exactly_targeted_hash_pinned_and_guarded(): void
    {
        $base = dirname(__DIR__, 2).'/..';
        $command = file_get_contents($base.'/app/Console/Commands/ApplyReviewedSalesChallanMigrationsCommand.php');
        $tableMigration = hash_file('sha256', $base.'/database/migrations/2026_08_14_000003_create_sales_challan_dispatch_tables.php');
        $seriesMigration = hash_file('sha256', $base.'/database/migrations/2026_08_14_000004_add_sales_challan_number_series_and_permissions.php');

        $this->assertStringContainsString("'2026_08_14_000003_create_sales_challan_dispatch_tables' => '{$tableMigration}'", $command);
        $this->assertStringContainsString("'2026_08_14_000004_add_sales_challan_number_series_and_permissions' => '{$seriesMigration}'", $command);
        $this->assertStringContainsString('{--backup-manifest=', $command);
        $this->assertStringContainsString('{--writes-stopped', $command);
        $this->assertStringContainsString('authorizeReviewedLiveMigration(self::DATABASE)', $command);
        $this->assertStringContainsString('revokeDestructiveAuthorization()', $command);
        $this->assertStringContainsString('$migrator->run($paths', $command);
        $this->assertStringContainsString("'sales_challans'", $command);
        $this->assertStringContainsString("'packaging_roll_allocations'", $command);
    }
}
