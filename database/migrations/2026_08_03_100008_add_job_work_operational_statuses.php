<?php

use App\Enums\InventoryReceiptStatus;
use App\Enums\JobWorkStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_mill_dispatches', function (Blueprint $table) {
            $table->string('job_work_status', 40)->nullable()->default('dispatched')->index();
        });
        Schema::table('stock_mill_dispatch_items', function (Blueprint $table) {
            $table->string('receipt_status', 40)->nullable()->default('pending')->index();
        });
        Schema::table('receive_stock_mill_dispatches', function (Blueprint $table) {
            $table->string('receipt_status', 40)->nullable()->default('received')->index();
        });
        Schema::table('receive_stock_mill_dispatch_items', function (Blueprint $table) {
            $table->string('receipt_status', 40)->nullable()->default('received')->index();
        });

        DB::table('stock_mill_dispatch_items')->where('status', '!=', 'Deleted')->update([
            'receipt_status' => InventoryReceiptStatus::Pending->value,
        ]);
        DB::table('stock_mill_dispatch_items')->where('status', 'Deleted')->update(['receipt_status' => null]);
        DB::table('receive_stock_mill_dispatches')->where('status', '!=', 'Deleted')->update([
            'receipt_status' => InventoryReceiptStatus::Received->value,
        ]);
        DB::table('receive_stock_mill_dispatches')->where('status', 'Deleted')->update(['receipt_status' => null]);
        DB::table('receive_stock_mill_dispatch_items')->where('status', '!=', 'Deleted')->update([
            'receipt_status' => InventoryReceiptStatus::Received->value,
        ]);
        DB::table('receive_stock_mill_dispatch_items')->where('status', 'Deleted')->update(['receipt_status' => null]);

        DB::table('stock_mill_dispatches')->where('status', '!=', 'Deleted')->orderBy('id')->each(
            function (object $dispatch): void {
                $status = match (true) {
                    (bool) $dispatch->is_tot_mtr_received => JobWorkStatus::Received,
                    (float) $dispatch->tot_receive_mtr > 0 => JobWorkStatus::PartiallyReceived,
                    default => JobWorkStatus::Dispatched,
                };

                DB::table('stock_mill_dispatches')->where('id', $dispatch->id)->update([
                    'job_work_status' => $status->value,
                ]);
            }
        );
        DB::table('stock_mill_dispatches')->where('status', 'Deleted')->update(['job_work_status' => null]);
    }

    public function down(): void
    {
        Schema::table('receive_stock_mill_dispatch_items', function (Blueprint $table) {
            $table->dropIndex('receive_stock_mill_dispatch_items_receipt_status_index');
            $table->dropColumn('receipt_status');
        });
        Schema::table('receive_stock_mill_dispatches', function (Blueprint $table) {
            $table->dropIndex('receive_stock_mill_dispatches_receipt_status_index');
            $table->dropColumn('receipt_status');
        });
        Schema::table('stock_mill_dispatch_items', function (Blueprint $table) {
            $table->dropIndex('stock_mill_dispatch_items_receipt_status_index');
            $table->dropColumn('receipt_status');
        });
        Schema::table('stock_mill_dispatches', function (Blueprint $table) {
            $table->dropIndex('stock_mill_dispatches_job_work_status_index');
            $table->dropColumn('job_work_status');
        });
    }
};
