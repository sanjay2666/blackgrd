<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        $companyIds = DB::table('process_items')->where('status', 'Active')->whereNotNull('company_id')
            ->distinct()->orderBy('company_id')->pluck('company_id');

        foreach ($companyIds as $companyId) {
            $departments = DB::table('departments')->where('company_id', $companyId)->whereNull('factory_id')
                ->where('status', '!=', 'Deleted')->orderBy('id')->get()->keyBy(fn ($department) => strtolower(trim($department->department_name)));

            if ($departments->has('warehose') && ! $departments->has('warehouse')) {
                $warehouse = $departments->get('warehose');
                DB::table('departments')->where('id', $warehouse->id)->update(['department_name' => 'Warehouse', 'updated_at' => now()]);
                $warehouse->department_name = 'Warehouse';
                $departments->put('warehouse', $warehouse);
            }

            foreach (['Weaving', 'Dyeing', 'Printing', 'Coating', 'Packaging', 'Warehouse'] as $departmentName) {
                $key = strtolower($departmentName);
                $department = $departments->get($key);

                if ($department === null) {
                    $department = (object) [
                        'id' => DB::table('departments')->insertGetId([
                            'company_id' => $companyId,
                            'department_name' => $departmentName,
                            'status' => 'Active',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]),
                        'department_name' => $departmentName,
                        'status' => 'Active',
                    ];
                    $departments->put($key, $department);
                } elseif ($department->status !== 'Active') {
                    DB::table('departments')->where('id', $department->id)->update(['status' => 'Active', 'updated_at' => now()]);
                    $department->status = 'Active';
                }
            }

            DB::table('process_items')->where('company_id', $companyId)->where('status', 'Active')->orderBy('id')->get(['id', 'process_name', 'department_id'])
                ->each(function ($process) use ($departments): void {
                    $departmentName = match (strtolower(trim($process->process_name))) {
                        'warping', 'weaving' => 'Weaving',
                        'dyeing' => 'Dyeing',
                        'printing', 'd-printing', 'c-printing' => 'Printing',
                        'coating' => 'Coating',
                        'packaging' => 'Packaging',
                        'warehouse' => 'Warehouse',
                        default => null,
                    };

                    if ($departmentName !== null) {
                        $departmentId = $departments->get(strtolower($departmentName))->id;
                        if ((int) $process->department_id !== (int) $departmentId) {
                            DB::table('process_items')->where('id', $process->id)->update(['department_id' => $departmentId]);
                        }
                    }
                });
        }
    }

    public function down(): void
    {
        // Master-data changes are intentionally not reversed: removing Department
        // records or mappings could invalidate subsequently assigned user access.
    }
};
