<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_order_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('work_order_id')->nullable();
            $table->integer('customer_id')->nullable();
            $table->integer('sale_order_id')->nullable();
            $table->integer('sale_order_item_id')->nullable();
            $table->integer('item_type_id')->nullable();
            $table->integer('unit_type_id')->nullable();
            $table->integer('item_id')->nullable();
            $table->string('hsn', 11)->nullable();
            $table->integer('pcs');
            $table->integer('cut');
            $table->decimal('meter', 10, 2);
            $table->string('grey_quality', 555)->nullable();
            $table->string('dyeing_color', 555)->nullable();
            $table->string('coating_type', 555)->nullable();
            $table->string('extra_job', 555)->nullable();
            $table->string('print_job', 555)->nullable();
            $table->date('expect_delivery_date')->nullable();
            $table->string('order_item_priority', 222);
            $table->integer('quantity')->nullable();
            $table->enum('is_work_completed', ['0', '1'])->default('0');
            $table->integer('deleted_by')->nullable();
            $table->dateTime('deleted_date')->nullable();
            $table->char('financial_year', 4)->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_items');
    }
};
