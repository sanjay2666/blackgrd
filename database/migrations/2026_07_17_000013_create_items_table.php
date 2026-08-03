<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->increments('item_id');
            $table->string('item_name')->nullable();
            $table->string('item_code')->nullable();
            $table->string('internal_item_name')->nullable();
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->string('hsncode', 40)->nullable();
            $table->integer('item_type_id')->default(0);
            $table->integer('unit_type_id')->default(0);
            $table->string('clr_category')->nullable();
            $table->decimal('cut', 10, 2)->nullable();
            $table->decimal('pur_rate', 10, 2)->nullable();
            $table->decimal('sale_rate', 10, 2)->nullable();
            $table->float('igst')->nullable();
            $table->float('sgst')->nullable();
            $table->float('cgst')->nullable();
            $table->float('sale_igst')->nullable();
            $table->float('sale_cgst')->nullable();
            $table->float('sale_sgst')->nullable();
            $table->integer('item_gsm')->nullable();
            $table->string('item_final_gsm', 25)->nullable();
            $table->string('item_width', 22)->nullable();
            $table->string('item_final_width', 25)->nullable();
            $table->text('remarks')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->dateTime('created')->nullable();
            $table->dateTime('modified')->nullable();
            $table->integer('created_by')->default(0);
            $table->integer('modified_by')->default(0);
            $table->boolean('is_conusmable')->default(false);
            $table->boolean('is_outsourced')->default(false);
            $table->boolean('is_jobwork')->default(false);
            $table->enum('is_lab_test_required', ['Yes', 'No'])->default('No');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
