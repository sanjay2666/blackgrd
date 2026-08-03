<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('customer_id')->nullable();
            $table->integer('billing_id')->nullable();
            $table->integer('shipping_id')->nullable();
            $table->string('sale_order_type', 20)->nullable();
            $table->date('sale_order_date')->nullable();
            $table->string('sale_order_number', 60)->nullable();
            $table->string('sales_order', 60)->nullable();
            $table->string('sale_order_from', 255)->nullable();
            $table->string('order_priority', 20)->nullable();
            $table->enum('development_type', ['Bulk', 'Sample', 'JobWork'])->default('Bulk');
            $table->string('order_slip_file', 2555)->nullable();
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->integer('items')->nullable();
            $table->integer('lot_number')->nullable();
            $table->string('ind_agent_id', 60)->nullable();
            $table->decimal('subtotal', 10, 2)->nullable();
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->decimal('freight', 10, 2)->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->decimal('total_amount_without_roundoff', 10, 2)->nullable();
            $table->integer('roundoff')->nullable();
            $table->integer('total_amount_after_roundoff')->nullable();
            $table->integer('order_by_employee');
            $table->integer('cancel_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->enum('is_return', ['Yes', 'No'])->default('No');
            $table->integer('executed_by')->nullable();

            $table->char('financial_year', 4)->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('modified_at')->nullable();

            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_orders');
    }
};