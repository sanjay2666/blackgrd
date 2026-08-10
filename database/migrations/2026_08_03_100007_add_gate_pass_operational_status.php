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
        Schema::table('gate_passes', function (Blueprint $table) {
            $table->string('gate_pass_status', 40)->nullable()->default('draft')->index();
        });

        $mapper = app(LegacyOperationalStatusMapper::class);

        DB::table('gate_passes')->orderBy('id')->each(function (object $gatePass) use ($mapper): void {
            DB::table('gate_passes')->where('id', $gatePass->id)->update([
                'gate_pass_status' => $mapper->gatePass(
                    $gatePass->status,
                    $gatePass->is_item_received_in_warehouse === 'Yes',
                    $gatePass->gatepass_number,
                )?->value,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('gate_passes', function (Blueprint $table) {
            $table->dropIndex('gate_passes_gate_pass_status_index');
            $table->dropColumn('gate_pass_status');
        });
    }
};
