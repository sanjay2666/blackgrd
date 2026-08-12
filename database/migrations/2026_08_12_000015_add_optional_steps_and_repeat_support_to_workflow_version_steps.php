<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    private const PROCESS_UNIQUE_INDEX = 'workflow_version_steps_workflow_version_id_process_id_unique';

    public function up(): void
    {
        Schema::table('workflow_version_steps', function (Blueprint $table): void {
            $table->boolean('is_required')->default(true)->after('sequence');
            $table->dropUnique(self::PROCESS_UNIQUE_INDEX);
        });
    }

    public function down(): void
    {
        if (DB::table('workflow_version_steps')->where('is_required', false)->exists()) {
            throw new RuntimeException('Cannot roll back optional Workflow Version Steps without losing their required/optional semantics.');
        }

        if (DB::table('workflow_version_steps')
            ->select('workflow_version_id')
            ->groupBy('workflow_version_id', 'process_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists()) {
            throw new RuntimeException('Cannot restore the prior unique Process restriction while repeated Workflow Version Steps exist.');
        }

        Schema::table('workflow_version_steps', function (Blueprint $table): void {
            $table->unique(['workflow_version_id', 'process_id'], self::PROCESS_UNIQUE_INDEX);
            $table->dropColumn('is_required');
        });
    }
};
