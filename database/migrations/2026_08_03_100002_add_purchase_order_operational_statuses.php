<?php

use App\Domain\OperationalStatus\LegacyOperationalStatusMapper;
use App\Enums\InventoryReceiptStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('document_status', 40)->nullable()->default('draft')->index();
        });
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->string('receipt_status', 40)->nullable()->default('pending')->index();
        });

        $mapper = app(LegacyOperationalStatusMapper::class);

        DB::table('purchase_order_items')->orderBy('id')->each(function (object $item) use ($mapper): void {
            if ($item->status === 'Deleted' || (bool) $item->is_deleted) {
                DB::table('purchase_order_items')->where('id', $item->id)->update(['receipt_status' => null]);

                return;
            }

            DB::table('purchase_order_items')->where('id', $item->id)->update([
                'receipt_status' => $mapper->purchaseReceipt(
                    (float) ($item->quantity ?? 0),
                    (float) ($item->received_quantity ?? 0),
                )->value,
            ]);
        });

        DB::table('purchase_orders')->orderBy('id')->each(function (object $order) use ($mapper): void {
            if ($order->status === 'Deleted' || $order->is_deleted === 'Yes') {
                DB::table('purchase_orders')->where('id', $order->id)->update(['document_status' => null]);

                return;
            }

            $lineStatuses = DB::table('purchase_order_items')
                ->where('purchase_id', $order->id)
                ->where('status', '!=', 'Deleted')
                ->where('is_deleted', false)
                ->whereNotNull('receipt_status')
                ->pluck('receipt_status')
                ->map(fn (string $status): InventoryReceiptStatus => InventoryReceiptStatus::from($status))
                ->all();

            DB::table('purchase_orders')->where('id', $order->id)->update([
                'document_status' => $mapper->purchaseOrder($lineStatuses)->value,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropIndex('purchase_order_items_receipt_status_index');
            $table->dropColumn('receipt_status');
        });
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex('purchase_orders_document_status_index');
            $table->dropColumn('document_status');
        });
    }
};
