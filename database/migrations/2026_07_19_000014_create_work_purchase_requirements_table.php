<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_purchase_requirements', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('work_order_id')->nullable();
            $table->integer('work_order_item_id')->nullable();
            $table->integer('item_id')->nullable();
            $table->integer('item_type_id')->nullable();
            $table->integer('unit_type_id')->nullable();
            $table->decimal('required_quantity', 12, 2)->default(0.00);
            $table->decimal('purchase_quantity', 12, 2)->default(0.00);
            $table->decimal('balance_quantity', 12, 2)->default(0.00);
            $table->date('required_date')->nullable();
            $table->boolean('is_purchase_order_created')->default(0);
            $table->text('remarks')->nullable();
            $table->char('financial_year', 4)->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->index(['work_order_id'], 'work_purchase_requirements_work_order_id_index');
            $table->index(['work_order_item_id'], 'work_purchase_requirements_work_order_item_id_index');
            $table->index(['item_id'], 'work_purchase_requirements_item_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_purchase_requirements');
    }
};
