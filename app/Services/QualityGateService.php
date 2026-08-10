<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\FrontendPermissionCatalog;
use App\Support\PermissionRegistry;
use App\Support\RoleTemplateCatalog;
use App\Support\RoutePermissionRegistry;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;

final class QualityGateService
{
    /** @return list<string> */
    public function permissionRegistryErrors(): array
    {
        $permissions = PermissionRegistry::all();
        $keys = array_column($permissions, 'key');
        $errors = [];

        foreach (array_keys(array_filter(array_count_values($keys), fn (int $count): bool => $count > 1)) as $key) {
            $errors[] = "Duplicate canonical permission [{$key}].";
        }

        $canonical = array_fill_keys($keys, true);
        foreach (RoleTemplateCatalog::all() as $role => $rolePermissions) {
            foreach ($rolePermissions as $permission) {
                if (!isset($canonical[$permission])) {
                    $errors[] = "Role template [{$role}] references unknown permission [{$permission}].";
                }
            }
        }

        $frontend = FrontendPermissionCatalog::keys();
        foreach ($frontend as $permission) {
            if (!isset($canonical[$permission])) {
                $errors[] = "Frontend catalog references unknown permission [{$permission}].";
            }
        }

        $adminOnlyResources = ['companies', 'roles', 'users', 'security', 'settings', 'audit-logs', 'number-series'];
        foreach ($frontend as $permission) {
            if (in_array(strtok($permission, '.'), $adminOnlyResources, true) || $permission === 'organization.access-manage') {
                $errors[] = "Admin-only permission [{$permission}] is frontend assignable.";
            }
        }

        return array_values(array_unique($errors));
    }

    /** @param Collection<int, Route>|iterable<Route> $routes @return list<string> */
    public function routeCoverageErrors(iterable $routes): array
    {
        $routes = collect($routes);
        $errors = [];
        $excluded = config('rbac_routes.excluded_authenticated', []);

        foreach ($routes as $route) {
            if (!$this->isAuthenticated($route)) {
                continue;
            }

            if (RoutePermissionRegistry::permission($route) === null && !in_array($route->getName(), $excluded, true)) {
                $errors[] = 'Authenticated route has no RBAC decision: '.$route->methods()[0].' '.$route->uri().' ['.$route->getName().'].';
            }
        }

        $configuredNames = array_keys(config('rbac_routes.admin_custom', []) + config('rbac_routes.frontend_named', []));
        $routeNames = $routes->map(fn (Route $route): ?string => $route->getName())->filter()->all();
        foreach ($configuredNames as $name) {
            if (!in_array($name, $routeNames, true)) {
                $errors[] = "Stale RBAC route mapping [{$name}].";
            }
        }

        return $errors;
    }

    /** @return list<string> */
    public function sourceFoundationErrors(): array
    {
        $errors = [];
        $required = [
            'tests/Unit/Audit/AuditLogContractTest.php',
            'tests/Unit/Auth/AuthenticationSecurityContractTest.php',
            'tests/Unit/NumberSeries/NumberSeriesContractTest.php',
            'tests/Unit/Organization/FinancialYearContractTest.php',
        ];

        foreach ($required as $file) {
            if (!is_file(base_path($file))) {
                $errors[] = "Required foundation regression test is missing [{$file}].";
            }
        }

        $auditRoutes = collect(app('router')->getRoutes())->filter(fn (Route $route): bool => str_contains((string) $route->getName(), 'audit-logs'));
        foreach ($auditRoutes as $route) {
            if (array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                $errors[] = 'Audit log route is mutating: '.$route->methods()[0].' '.$route->uri().'.';
            }
        }

        foreach (['app/Services/NumberSeriesService.php', 'app/Services/FinancialYearResolver.php'] as $file) {
            if (!is_file(base_path($file))) {
                $errors[] = "Foundation service is missing [{$file}].";
            }
        }

        return $errors;
    }

    /** @return list<string> */
    public function changedMigrationErrors(string $baseline = 'HEAD'): array
    {
        $errors = [];
        foreach ($this->changedFiles('database/migrations') as $relative) {
            if ($relative === '' || !is_file(base_path($relative))) {
                continue;
            }

            $contents = file_get_contents(base_path($relative));
            if ($contents === false) {
                $errors[] = "Unable to read changed migration [{$relative}].";
                continue;
            }

            if (preg_match('/\b(?:drop(?:IfExists|Column)?|truncate|delete\s+from|migrate:fresh|schema:drop)\b/i', $contents)) {
                $errors[] = "Changed migration contains a destructive pattern and needs explicit review [{$relative}].";
            }
        }

        return $errors;
    }

    /** @return list<string> */
    public function changedPhpFiles(string $baseline = 'HEAD'): array
    {
        return $this->changedFiles('*.php');
    }

    /** @return list<string> */
    private function changedFiles(string $pathspec): array
    {
        $output = $this->runGit(['status', '--porcelain', '--', $pathspec]);
        if ($output['code'] !== 0) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn (string $line): string => trim(substr($line, 3)), preg_split('/\R/', trim($output['output'])) ?: []),
            fn (string $file): bool => $file !== '' && is_file(base_path($file))
        ));
    }

    /** @return array{code:int,output:string} */
    private function runGit(array $arguments): array
    {
        $process = new \Symfony\Component\Process\Process(array_merge(['git'], $arguments), base_path());
        $process->run();

        return ['code' => $process->getExitCode() ?? 1, 'output' => $process->getOutput().$process->getErrorOutput()];
    }

    private function isAuthenticated(Route $route): bool
    {
        return collect($route->middleware())->contains(fn (string $middleware): bool => str_starts_with($middleware, 'auth:'));
    }
}
