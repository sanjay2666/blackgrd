<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receive_stock_mill_dispatch_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('receive_mill_dispatch_id')->nullable();
            $table->integer('stock_mill_dispatch_id')->nullable();
            $table->integer('stock_mill_dispatch_item_id')->nullable();
            $table->integer('item_type_id')->nullable();
            $table->integer('item_id')->nullable();
            $table->string('item_name', 255)->nullable();
            $table->string('dyeing_color', 255)->nullable();
            $table->string('coated_pvc', 25)->nullable();
            $table->string('extra_job', 25)->nullable();
            $table->string('print_job', 25)->nullable();
            $table->string('dyeing_taka_number', 25)->nullable();
            $table->string('hsn', 255)->nullable();
            $table->integer('qty')->nullable();
            $table->integer('unit_type_id')->nullable();
            $table->decimal('received_mtr', 10, 2)->nullable();
            $table->decimal('meter', 10, 2)->nullable();
            $table->string('taka_number', 22)->nullable();
            $table->string('dyeing_lot_number', 25)->nullable();
            $table->string('remarks', 555)->nullable();
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
        Schema::dropIfExists('receive_stock_mill_dispatch_items');
    }
};
