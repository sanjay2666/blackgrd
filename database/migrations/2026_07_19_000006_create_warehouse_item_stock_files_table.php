<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_item_stock_files', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('warehouse_item_id')->nullable();
            $table->integer('wis_id')->nullable();
            $table->integer('wis_out_id')->nullable();
            $table->string('invoice_copy_file', 255)->nullable();
            $table->string('packing_slip_file', 255)->nullable();
            $table->string('eway_bill_file', 255)->nullable();
            $table->string('lr_copy_file', 255)->nullable();
            $table->integer('vendor_id')->nullable();
            $table->string('invoice_number', 555)->nullable();
            $table->string('bill_front_img', 555)->nullable();
            $table->string('bill_back_img', 555)->nullable();
            $table->string('challan_num', 22)->nullable();
            $table->char('financial_year', 4)->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->index(['warehouse_item_id'], 'warehouse_item_stock_files_warehouse_item_id_index');
            $table->index(['wis_id'], 'warehouse_item_stock_files_wis_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_item_stock_files');
    }
};
