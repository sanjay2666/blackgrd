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
        Schema::table('sale_orders', function (Blueprint $table) {
            $table->string('document_status', 40)->nullable()->default('draft')->index();
        });

        $mapper = app(LegacyOperationalStatusMapper::class);

        DB::table('sale_orders')->orderBy('id')->each(function (object $order) use ($mapper): void {
            if ($order->status === 'Deleted') {
                DB::table('sale_orders')->where('id', $order->id)->update(['document_status' => null]);

                return;
            }

            $items = DB::table('sale_order_items')
                ->where('sale_order_id', $order->id)
                ->where('status', '!=', 'Deleted')
                ->where('is_deleted', false)
                ->get(['id', 'is_work_completed', 'is_work_final_completed']);
            $itemIds = $items->pluck('id');
            $hasWorkOrder = $itemIds->isNotEmpty() && DB::table('work_order_items')
                ->whereIn('sale_order_item_id', $itemIds)
                ->where('status', '!=', 'Deleted')
                ->exists();
            $productionComplete = $items->isNotEmpty() && $items->every(
                fn (object $item): bool => (int) $item->is_work_completed === 1 || (int) $item->is_work_final_completed === 1
            );

            DB::table('sale_orders')->where('id', $order->id)->update([
                'document_status' => $mapper->saleOrder($hasWorkOrder, $productionComplete)->value,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('sale_orders', function (Blueprint $table) {
            $table->dropIndex('sale_orders_document_status_index');
            $table->dropColumn('document_status');
        });
    }
};
