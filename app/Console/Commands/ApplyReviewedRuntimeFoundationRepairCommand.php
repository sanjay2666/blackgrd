<?php

namespace App\Console\Commands;

use App\DatabaseSafety\DatabaseSafetyGuard;
use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

final class ApplyReviewedRuntimeFoundationRepairCommand extends Command
{
    private const DATABASE = 'blackgrd';

    private const MIGRATIONS = [
        '2026_08_11_000002_create_user_department_access_table' => 'd2844b567b303578c4cfe9e99c7eaaac2b2f4eef1e37c5f1afbbad8b5f685cdb',
        '2026_08_11_000006_create_dyeing_colours_table' => '643b15b01c65c817bcd17f61d379fcb8af790bfdd712242a9a0358faa2fc91fa',
        '2026_08_12_000012_repair_runtime_master_schema_drift' => '8a282787490c09a2b736a37693f7b77fcb2fd3bd2635862cfd780ff26b347498',
        '2026_08_12_000013_add_company_scope_to_sale_order_items' => '5ef7e731de98d554e4247aac175a4bfbc7beaf7f51079d68c5a7bfcf05f56d46',
    ];

    protected $signature = 'db:apply-reviewed-runtime-foundation
        {--execute : Apply only the reviewed runtime foundation repairs}
        {--confirm-database= : Must exactly equal blackgrd}
        {--backup-manifest= : JSON manifest for verified backups}
        {--writes-stopped : Confirm queue and scheduler writes were independently verified stopped}';

    protected $description = 'Apply reviewed additive repairs for current runtime schema blockers';

    public function handle(DatabaseSafetyGuard $guard, Migrator $migrator): int
    {
        try {
            $snapshot = $guard->inspect();
            foreach (['configuredDatabase', 'declaredDatabase', 'connectedDatabase'] as $field) {
                if ($snapshot->{$field} !== self::DATABASE) {
                    throw new RuntimeException("{$field} must exactly equal [".self::DATABASE.'].');
                }
            }
            if (! app()->isDownForMaintenance() || ! $this->option('writes-stopped')) {
                throw new RuntimeException('Maintenance mode and --writes-stopped are required.');
            }

            $paths = [];
            foreach (self::MIGRATIONS as $migration => $hash) {
                $path = database_path('migrations/'.$migration.'.php');
                if (! File::isFile($path)) {
                    throw new RuntimeException("Reviewed migration [{$migration}] is missing.");
                }
                if (! hash_equals($hash, (string) hash_file('sha256', $path))) {
                    throw new RuntimeException("Reviewed migration [{$migration}] hash mismatch.");
                }
                $paths[] = $path;
            }
            $this->verifyBackupManifest((string) $this->option('backup-manifest'));

            $before = $this->preservationSnapshot();
            if (! $this->option('execute')) {
                $this->info('READY: reviewed runtime foundation preflight passed; no migration was executed.');

                return self::SUCCESS;
            }
            if ((string) $this->option('confirm-database') !== self::DATABASE) {
                throw new RuntimeException('--execute requires --confirm-database=blackgrd.');
            }

            $migrator->setOutput($this->output);
            foreach ($paths as $path) {
                $migration = pathinfo($path, PATHINFO_FILENAME);
                if (! DB::table('migrations')->where('migration', $migration)->exists()) {
                    $migrator->run([$path], ['step' => true]);
                }
            }

            $this->verifyAppliedSchema($before);
            $this->info('PASS: reviewed runtime foundation repairs applied with preservation verification.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('BLOCKED/FAILED: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function verifyBackupManifest(string $path): void
    {
        if ($path === '' || ! File::isFile($path)) {
            throw new RuntimeException('A readable backup manifest is required.');
        }
        $manifest = json_decode((string) File::get($path), true, flags: JSON_THROW_ON_ERROR);
        if (($manifest['database'] ?? null) !== self::DATABASE) {
            throw new RuntimeException('Backup manifest database must equal blackgrd.');
        }
        $kinds = [];
        foreach (($manifest['backups'] ?? []) as $backup) {
            $file = (string) ($backup['path'] ?? '');
            if (! in_array($backup['kind'] ?? null, ['full', 'affected_tables', 'migrations_table'], true)
                || ! File::isFile($file)
                || File::size($file) !== (int) ($backup['size'] ?? -1)
                || ! hash_equals(strtolower((string) ($backup['sha256'] ?? '')), strtolower((string) hash_file('sha256', $file)))) {
                throw new RuntimeException('Backup checksum, size, or manifest entry is invalid.');
            }
            $kinds[] = $backup['kind'];
        }
        sort($kinds);
        if ($kinds !== ['affected_tables', 'full', 'migrations_table']) {
            throw new RuntimeException('Backup manifest must contain full, affected_tables, and migrations_table backups.');
        }
    }

    /** @return array<string, mixed> */
    private function preservationSnapshot(): array
    {
        return [
            'users' => DB::table('users')->count(),
            'companies' => DB::table('companies')->count(),
            'departments' => DB::table('departments')->count(),
            'cotings' => DB::table('cotings')->count(),
            'dyeing_colours' => Schema::hasTable('dyeing_colours') ? DB::table('dyeing_colours')->count() : 0,
            'sale_order_items' => DB::table('sale_order_items')->orderBy('id')->get(['id', 'sale_order_id', 'meter', 'status'])->map(fn ($row): array => (array) $row)->all(),
        ];
    }

    /** @param array<string, mixed> $before */
    private function verifyAppliedSchema(array $before): void
    {
        foreach (['user_department_access', 'dyeing_colours'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Required runtime table [{$table}] was not created.");
            }
        }
        foreach (['description', 'display_order'] as $column) {
            if (! Schema::hasColumn('cotings', $column)) {
                throw new RuntimeException("Required Coting column [{$column}] was not created.");
            }
        }
        if (! Schema::hasColumn('sale_order_items', 'company_id')
            || DB::table('sale_order_items')->whereNull('company_id')->exists()
            || DB::table('sale_order_items as item')->join('sale_orders as order', 'order.id', '=', 'item.sale_order_id')
                ->whereColumn('item.company_id', '!=', 'order.company_id')->exists()) {
            throw new RuntimeException('Sale order item company ownership repair verification failed.');
        }
        if ($before !== $this->preservationSnapshot()) {
            throw new RuntimeException('Existing row-count preservation check failed.');
        }
    }
}
