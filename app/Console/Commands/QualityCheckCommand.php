<?php

namespace App\Console\Commands;

use App\DatabaseSafety\DatabaseSafetyGuard;
use App\Services\QualityGateService;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

final class QualityCheckCommand extends Command
{
    protected $signature = 'quality:check {--quick : Skip the full test suite and Blade cache}';

    protected $description = 'Run the read-only ERP Foundation quality gate';

    /** @var array<string, string> */
    private array $results = [];

    public function handle(DatabaseSafetyGuard $guard, QualityGateService $checks): int
    {
        $this->check('Database Safety', function () use ($guard): string {
            $result = $guard->check();
            $snapshot = $result->snapshot;

            if ($snapshot->connectionError !== null) {
                return 'FAIL: database could not be inspected.';
            }

            if ($snapshot->environment === 'testing' && $result->allowed && !$result->executionArmed) {
                return 'PASS: testing database allowed, destructive execution not armed.';
            }

            if (! $result->allowed && ! $result->executionArmed) {
                return 'PASS: BLOCKED / NOT ARMED (destructive access remains protected).';
            }

            return 'FAIL: safety result is unexpectedly armed or not protected.';
        });

        $this->check('RBAC Coverage', fn (): array => $checks->routeCoverageErrors(app('router')->getRoutes()));
        $this->check('Permission Registry', fn (): array => $checks->permissionRegistryErrors());
        $this->check('Audit Integrity', fn (): array => $checks->sourceFoundationErrors());
        $this->check('Number Series', fn (): array => $checks->sourceFoundationErrors());
        $this->check('Financial Year', fn (): array => $checks->sourceFoundationErrors());
        $this->check('Migration Safety', fn (): array => $checks->changedMigrationErrors());
        $this->check('Maintenance Mode', fn (): string => app()->isDownForMaintenance() ? 'FAIL: maintenance mode is enabled.' : 'PASS');

        $this->prepareTestEnvironment();
        $this->processCheck('PHP Tests', [PHP_BINARY, base_path('artisan'), 'test', '--no-ansi'], !$this->option('quick'), [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'SESSION_DRIVER' => 'array',
            'CACHE_STORE' => 'array',
            'QUEUE_CONNECTION' => 'sync',
        ]);
        $this->processCheck('Routes', [PHP_BINARY, base_path('artisan'), 'route:list', '--no-ansi']);
        $this->processCheck('Blade', [PHP_BINARY, base_path('artisan'), 'view:cache', '--no-ansi'], !$this->option('quick'));
        $changedPhp = $checks->changedPhpFiles();
        $this->processCheck('PHP Lint', $this->lintCommand($changedPhp), $changedPhp !== []);
        $this->processCheck('Pint', $this->pintCommand($changedPhp), $changedPhp !== []);
        $this->processCheck('Git Diff', ['git', 'diff', '--check']);

        $failed = array_filter($this->results, fn (string $result): bool => str_starts_with($result, 'FAIL'));
        $this->newLine();
        $this->line($failed === [] ? 'QUALITY GATE: PASSED' : 'QUALITY GATE: FAILED');

        return $failed === [] ? self::SUCCESS : self::FAILURE;
    }

    private function check(string $name, callable $callback): void
    {
        $result = $callback();
        $errors = is_array($result) ? $result : (str_starts_with($result, 'FAIL') ? [$result] : []);
        $status = $errors === [] ? 'PASS' : 'FAIL';
        $this->results[$name] = $status;
        $this->line(str_pad($name, 24, '.')." {$status}");
        if ($errors !== [] && ($this->getOutput()->isVerbose() || count($errors) < 4)) {
            foreach ($errors as $error) {
                $this->error('  '.$error);
            }
        }
    }

    /** @param list<string> $command @param array<string, string> $environment */
    private function processCheck(string $name, array $command, bool $enabled = true, array $environment = []): void
    {
        if (!$enabled) {
            $this->results[$name] = 'SKIP';
            $this->line(str_pad($name, 24, '.').' SKIP (quick mode)');
            return;
        }

        $process = new Process($command, base_path());
        if ($environment !== []) {
            $process->setEnv($environment);
        }
        $process->setTimeout(null);
        $process->run();
        $output = trim($process->getOutput().$process->getErrorOutput());
        $passed = $process->isSuccessful();
        $this->results[$name] = $passed ? 'PASS' : 'FAIL';
        $this->line(str_pad($name, 24, '.').' '.($passed ? 'PASS' : 'FAIL'));
        if ($passed && $name === 'PHP Tests') {
            $summaryLines = array_map('trim', preg_split('/\R/', $output) ?: []);
            foreach (preg_grep('/^(Tests:|Assertions:|Duration:)/', $summaryLines) ?: [] as $summary) {
                $this->line('  '.$summary);
            }
        }
        if (!$passed || $this->getOutput()->isVerbose()) {
            if ($output !== '') {
                $this->line($output);
            }
        }
    }

    /** @param list<string> $files @return list<string> */
    private function lintCommand(array $files): array
    {
        $script = 'foreach (array_slice($argv, 1) as $file) { passthru(PHP_BINARY." -l ".escapeshellarg($file), $code); if ($code !== 0) exit($code); }';

        return array_merge([PHP_BINARY, '-r', $script], $files);
    }

    /** @param list<string> $files @return list<string> */
    private function pintCommand(array $files): array
    {
        return array_merge([PHP_BINARY, base_path('vendor/bin/pint'), '--preset=psr12', '--test', '--no-interaction'], $files);
    }

    private function prepareTestEnvironment(): void
    {
        $process = new Process([PHP_BINARY, base_path('artisan'), 'config:clear', '--no-ansi'], base_path());
        $process->run();
    }
}
