<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_balance_items', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('ware_in_item_id')->nullable();
            $table->integer('ware_out_item_id')->nullable();
            $table->integer('warehouse_id')->nullable();
            $table->integer('ware_comp_id')->nullable();
            $table->integer('receiver_id')->nullable();
            $table->date('receive_date')->nullable();
            $table->integer('item_id')->nullable();
            $table->integer('item_type_id')->nullable();
            $table->integer('unit_type_id')->nullable();
            $table->integer('master_id')->nullable();
            $table->integer('machine_id')->nullable();
            $table->decimal('op_item_qty', 12, 2)->default(0.00);
            $table->decimal('in_item_qty', 12, 2)->default(0.00);
            $table->decimal('out_item_qty', 12, 2)->default(0.00);
            $table->decimal('item_qty', 12, 2)->default(0.00);
            $table->text('item_remark')->nullable();
            $table->string('grey_quality', 555)->nullable();
            $table->string('dyeing_color', 555)->nullable();
            $table->string('coating_type', 555)->nullable();
            $table->string('print_job', 555)->nullable();
            $table->text('extra_job')->nullable();
            $table->tinyInteger('balance_status')->default(1);
            $table->string('current_balance_key', 64)->nullable();
            $table->char('financial_year', 4)->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->unique(['current_balance_key'], 'warehouse_balance_items_current_balance_key_unique');
            $table->index(['ware_in_item_id'], 'warehouse_balance_items_ware_in_item_id_index');
            $table->index(['warehouse_id'], 'warehouse_balance_items_warehouse_id_index');
            $table->index(['ware_comp_id'], 'warehouse_balance_items_ware_comp_id_index');
            $table->index(['item_id'], 'warehouse_balance_items_item_id_index');
            $table->index(['item_type_id'], 'warehouse_balance_items_item_type_id_index');
            $table->index(['balance_status'], 'warehouse_balance_items_balance_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_balance_items');
    }
};
