<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->engine = 'MyISAM';
            $table->charset = 'utf8';
			$table->collation = 'utf8_unicode_ci';

            $table->increments('id');
            $table->unsignedInteger('purchase_id')->nullable();
            $table->unsignedInteger('item_id')->nullable();
            $table->integer('item_type_id')->nullable();
            $table->string('name', 128)->nullable();
            $table->string('colour_name')->nullable();
            $table->decimal('meter', 10, 2)->nullable();
            $table->decimal('quantity', 10, 2)->nullable()->default(0.00);
            $table->decimal('received_quantity', 10, 2)->nullable()->default(0.00);
            $table->decimal('balance_quantity', 10, 2)->nullable()->default(0.00);
            $table->decimal('mrp', 10, 2)->nullable();
            $table->decimal('cgst', 10, 2)->nullable();
            $table->decimal('sgst', 10, 2)->nullable();
            $table->decimal('igst', 10, 2)->nullable();
            $table->decimal('sgstrs', 10, 2)->nullable();
            $table->decimal('cgstrs', 10, 2)->nullable();
            $table->decimal('igstrs', 10, 2)->nullable();
            $table->decimal('saleprice_wot', 10, 2)->nullable()->comment('saleprice_without tax');
            $table->decimal('saleprice', 10, 2)->nullable();
            $table->decimal('total_price', 10, 2)->nullable();
            $table->decimal('cess', 10, 2)->nullable();
            $table->decimal('cessrs', 10, 2)->nullable();
            $table->decimal('taxrs', 10, 2)->nullable();
            $table->string('hsn', 55)->nullable();
            $table->string('unit', 55)->nullable();
            $table->enum('is_item_received_in_warehouse', ['0', '1'])->default('0');
            $table->boolean('is_deleted')->nullable()->default(false);
            $table->boolean('is_return')->nullable()->default(false);
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
        Schema::dropIfExists('purchase_order_items');
    }
};