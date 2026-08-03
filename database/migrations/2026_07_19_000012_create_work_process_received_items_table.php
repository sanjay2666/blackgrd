<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_process_received_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('work_order_id')->nullable();
            $table->integer('work_order_item_id')->nullable();
            $table->integer('work_process_requirement_id')->nullable();
            $table->integer('item_id')->nullable();
            $table->integer('item_type_id')->nullable();
            $table->integer('process_type_id')->nullable();
            $table->decimal('received_quantity', 12, 2)->default(0.00);
            $table->decimal('received_meter', 12, 2)->default(0.00);
            $table->date('received_date')->nullable();
            $table->integer('received_by')->nullable();
            $table->text('remarks')->nullable();
            $table->char('financial_year', 4)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('modified_at')->nullable();
            $table->integer('created_by')->default(0);
            $table->integer('modified_by')->default(0);
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->index(['work_order_id'], 'work_process_received_items_work_order_id_index');
            $table->index(['work_order_item_id'], 'work_process_received_items_work_order_item_id_index');
            $table->index(['work_process_requirement_id'], 'work_process_received_items_work_process_requirement_id_index');
            $table->index(['item_id'], 'work_process_received_items_item_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_process_received_items');
    }
};
