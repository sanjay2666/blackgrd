<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        $companyIds = DB::table('process_items')->where('status', 'Active')->where('process_name', 'Warping')
            ->whereNotNull('company_id')->distinct()->orderBy('company_id')->pluck('company_id');

        foreach ($companyIds as $companyId) {
            $warpingDepartment = DB::table('departments')->where('company_id', $companyId)->whereNull('factory_id')
                ->where('department_name', 'Warping')->where('status', '!=', 'Deleted')->orderBy('id')->first();

            if ($warpingDepartment === null) {
                $departmentId = DB::table('departments')->insertGetId([
                    'company_id' => $companyId,
                    'department_name' => 'Warping',
                    'status' => 'Active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $departmentId = $warpingDepartment->id;
                if ($warpingDepartment->status !== 'Active') {
                    DB::table('departments')->where('id', $departmentId)->update(['status' => 'Active', 'updated_at' => now()]);
                }
            }

            DB::table('process_items')->where('company_id', $companyId)->where('status', 'Active')
                ->where('process_name', 'Warping')->where(function ($query) use ($departmentId): void {
                    $query->whereNull('department_id')->orWhere('department_id', '!=', $departmentId);
                })
                ->update(['department_id' => $departmentId]);
        }
    }

    public function down(): void
    {
        // Master-data corrections remain in place: reverting can invalidate
        // legitimate Department Access assignments created after this update.
    }
};
