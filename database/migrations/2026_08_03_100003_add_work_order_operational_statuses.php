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
        Schema::table('work_orders', function (Blueprint $table) {
            $table->string('execution_status', 40)->nullable()->default('created')->index();
            $table->string('inspection_status', 40)->nullable()->default('pending')->index();
        });

        $mapper = app(LegacyOperationalStatusMapper::class);

        DB::table('work_orders')->orderBy('id')->each(function (object $workOrder) use ($mapper): void {
            $hasRequirement = DB::table('work_process_requirements')
                ->where('work_order_id', $workOrder->id)
                ->where('status', '!=', 'Deleted')
                ->exists();

            DB::table('work_orders')->where('id', $workOrder->id)->update([
                'execution_status' => $mapper->workOrder($workOrder, $hasRequirement)->value,
                'inspection_status' => $workOrder->insp_status === 'Complete'
                    ? InspectionStatus::Completed->value
                    : InspectionStatus::Pending->value,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropIndex('work_orders_execution_status_index');
            $table->dropIndex('work_orders_inspection_status_index');
            $table->dropColumn(['execution_status', 'inspection_status']);
        });
    }
};
