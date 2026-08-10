<?php

use App\Domain\OperationalStatus\LegacyOperationalStatusMapper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_process_requirements', function (Blueprint $table) {
            $table->string('requirement_status', 40)->nullable()->default('pending')->index();
            $table->string('allocation_status', 40)->nullable()->default('unallocated')->index();
        });

        $mapper = app(LegacyOperationalStatusMapper::class);

        DB::table('work_process_requirements')->orderBy('id')->each(function (object $requirement) use ($mapper): void {
            $decision = (int) $requirement->is_accept;
            $required = (float) ($requirement->quantity ?? 0);
            $allotted = (float) ($requirement->alloted_quantity ?? 0);

            DB::table('work_process_requirements')->where('id', $requirement->id)->update([
                'requirement_status' => $mapper->workRequirement($decision, $required, $allotted)->value,
                'allocation_status' => $mapper->allocation($required, $allotted, $decision)->value,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('work_process_requirements', function (Blueprint $table) {
            $table->dropIndex('work_process_requirements_requirement_status_index');
            $table->dropIndex('work_process_requirements_allocation_status_index');
            $table->dropColumn(['requirement_status', 'allocation_status']);
        });
    }
};
