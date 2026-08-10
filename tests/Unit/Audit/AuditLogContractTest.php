<?php

namespace Tests\Unit\Audit;

use App\Support\FrontendPermissionCatalog;
use App\Support\PermissionRegistry;
use Tests\TestCase;

final class AuditLogContractTest extends TestCase
{
    public function test_audit_schema_service_and_permissions_are_centralized(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_08_11_000001_create_audit_logs_table.php'));
        $logger = file_get_contents(base_path('app/Services/AuditLogger.php'));
        $this->assertStringContainsString("Schema::create('audit_logs'", $migration);
        $this->assertStringContainsString("enum('actor_type', ['Admin', 'User', 'System'])", $migration);
        $this->assertStringContainsString('recordAfterCommit', $logger);
        $this->assertStringContainsString("'password'", $logger);
        $this->assertCount(127, PermissionRegistry::all());
    }

    public function test_audit_model_is_application_level_append_only(): void
    {
        $model = file_get_contents(base_path('app/Models/AuditLog.php'));
        $this->assertStringContainsString('Audit history is append-only', $model);
        $this->assertStringContainsString('Audit history cannot be deleted', $model);
        $this->assertStringNotContainsString('public function update', $model);
    }

    public function test_audit_viewer_is_admin_rbac_protected_and_frontend_excluded(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $catalog = FrontendPermissionCatalog::keys();
        $this->assertStringContainsString('permission:audit-logs.view', $routes);
        $this->assertNotContains('audit-logs.view', $catalog);
        $this->assertNotContains('audit-logs.export', $catalog);
    }

    public function test_audit_logger_has_no_secret_payload_passthrough(): void
    {
        $logger = file_get_contents(base_path('app/Services/AuditLogger.php'));
        foreach (['password', 'password_confirmation', 'remember_token', 'otp', 'csrf', 'cookie', 'session', 'authorization'] as $secret) {
            $this->assertStringContainsString("'{$secret}'", $logger);
        }
        $this->assertStringNotContainsString('$request->all()', $logger);
    }
}
