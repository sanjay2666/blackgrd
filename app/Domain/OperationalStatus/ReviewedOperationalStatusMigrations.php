<?php

namespace App\Domain\OperationalStatus;

use Illuminate\Support\Facades\File;
use RuntimeException;

final class ReviewedOperationalStatusMigrations
{
    public const DATABASE = 'blackgrd';

    public const DISPOSABLE_DATABASE = 'blackgrd_schema_testing';

    /** @var array<string, array{file: string, sha256: string}> */
    public const MIGRATIONS = [
        '2026_08_03_100001_add_sale_order_operational_statuses' => [
            'file' => '2026_08_03_100001_add_sale_order_operational_statuses.php',
            'sha256' => 'dabbeffdcb522ae806f6108ea926df200322ded0037d4482d85125369a4e426e',
        ],
        '2026_08_03_100002_add_purchase_order_operational_statuses' => [
            'file' => '2026_08_03_100002_add_purchase_order_operational_statuses.php',
            'sha256' => '3da71f736d0c0534d028ca74384f3fe8d51c58900a5e30cb635fdde45d7c1c47',
        ],
        '2026_08_03_100003_add_work_order_operational_statuses' => [
            'file' => '2026_08_03_100003_add_work_order_operational_statuses.php',
            'sha256' => 'a2f2f57856f3d08c019fee233d81d2c45a834348c6fcf8acc549701812b23d0b',
        ],
        '2026_08_03_100004_add_work_requirement_operational_statuses' => [
            'file' => '2026_08_03_100004_add_work_requirement_operational_statuses.php',
            'sha256' => '26a71e28d10022091fced52f22bf411f570e350fc00270be7254057a2bbe5b03',
        ],
        '2026_08_03_100005_add_inspection_operational_statuses' => [
            'file' => '2026_08_03_100005_add_inspection_operational_statuses.php',
            'sha256' => '2138feae9d4fcb72dbb6e059963dce6c737830818d6635e7a5fda145cff3570b',
        ],
        '2026_08_03_100006_add_inventory_operational_statuses' => [
            'file' => '2026_08_03_100006_add_inventory_operational_statuses.php',
            'sha256' => 'd4e395ddd6b9287df1892955cb691ff1de4b313c2b9d13509e91775839253da9',
        ],
        '2026_08_03_100007_add_gate_pass_operational_status' => [
            'file' => '2026_08_03_100007_add_gate_pass_operational_status.php',
            'sha256' => '3bcf3cabcb66bc0d1cfaf791b4ee726a3f97edc7baef18a08798624b4fa159f2',
        ],
        '2026_08_03_100008_add_job_work_operational_statuses' => [
            'file' => '2026_08_03_100008_add_job_work_operational_statuses.php',
            'sha256' => '20acee8184858142996a854f03cf2f0eca137d6fb10cc289290a751e321703cc',
        ],
    ];

    /** @return list<string> */
    public function verifiedPaths(): array
    {
        $paths = [];

        foreach (self::MIGRATIONS as $migration) {
            $path = database_path('migrations/'.$migration['file']);

            if (! File::isFile($path)) {
                throw new RuntimeException("Reviewed migration file is missing: {$path}");
            }

            $actual = hash_file('sha256', $path);

            if ($actual === false || ! hash_equals($migration['sha256'], strtolower($actual))) {
                throw new RuntimeException("Reviewed migration hash mismatch: {$migration['file']}");
            }

            $paths[] = $path;
        }

        return $paths;
    }
}
