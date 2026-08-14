<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\PermissionRegistry;
use App\Support\RoleTemplateCatalog;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Symfony\Component\Process\Process;

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
                if (! isset($canonical[$permission])) {
                    $errors[] = "Role template [{$role}] references unknown permission [{$permission}].";
                }
            }
        }

        return array_values(array_unique($errors));
    }

    /** @param Collection<int, Route>|iterable<Route> $routes @return list<string> */
    public function routeCoverageErrors(iterable $routes): array
    {
        $routes = collect($routes);
        $errors = [];
        foreach ($routes as $route) {
            $middleware = collect($route->middleware());
            if (! $middleware->contains(fn (string $value): bool => str_starts_with($value, 'auth:web'))) {
                continue;
            }

            if (! $middleware->contains('frontend-page')) {
                $errors[] = 'Authenticated Frontend route lacks page-permission enforcement: '.$route->methods()[0].' '.$route->uri().' ['.$route->getName().'].';
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
            if (! is_file(base_path($file))) {
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
            if (! is_file(base_path($file))) {
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
            if ($relative === '' || ! is_file(base_path($relative))) {
                continue;
            }

            $contents = file_get_contents(base_path($relative));
            if ($contents === false) {
                $errors[] = "Unable to read changed migration [{$relative}].";

                continue;
            }

            $forwardMigration = preg_match('/public function up\(\):?\s*void\s*\{(.*?)\}\s*public function down/s', $contents, $match) === 1 ? $match[1] : $contents;
            if (preg_match('/\b(?:drop(?:IfExists|Column)?|truncate|delete\s+from|migrate:fresh|schema:drop)\b/i', $forwardMigration)) {
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
        $process = new Process(array_merge(['git'], $arguments), base_path());
        $process->run();

        return ['code' => $process->getExitCode() ?? 1, 'output' => $process->getOutput().$process->getErrorOutput()];
    }
}
