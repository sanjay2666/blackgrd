<?php

use App\Domain\OperationalStatus\LegacyOperationalStatusMapper;
use App\Enums\InspectionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_inspections', function (Blueprint $table) {
            $table->string('inspection_status', 40)->nullable()->default('pending')->index();
            $table->string('inspection_result', 40)->nullable()->default('pending')->index();
        });
        Schema::table('work_inspection_details', function (Blueprint $table) {
            $table->string('inspection_result', 40)->nullable()->default('pending')->index();
        });

        $mapper = app(LegacyOperationalStatusMapper::class);

        DB::table('work_inspections')->orderBy('id')->each(function (object $inspection) use ($mapper): void {
            DB::table('work_inspections')->where('id', $inspection->id)->update([
                'inspection_status' => $inspection->insp_status === 'Complete'
                    ? InspectionStatus::Completed->value
                    : InspectionStatus::Pending->value,
                'inspection_result' => $mapper->inspectionResult($inspection->insp_work_status)?->value,
            ]);
        });

        DB::table('work_inspection_details')->orderBy('id')->each(function (object $detail) use ($mapper): void {
            DB::table('work_inspection_details')->where('id', $detail->id)->update([
                'inspection_result' => $mapper->inspectionResult($detail->work_status)?->value,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('work_inspection_details', function (Blueprint $table) {
            $table->dropIndex('work_inspection_details_inspection_result_index');
            $table->dropColumn('inspection_result');
        });
        Schema::table('work_inspections', function (Blueprint $table) {
            $table->dropIndex('work_inspections_inspection_status_index');
            $table->dropIndex('work_inspections_inspection_result_index');
            $table->dropColumn(['inspection_status', 'inspection_result']);
        });
    }
};
