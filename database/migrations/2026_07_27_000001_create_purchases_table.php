<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('purchase_order_id')->nullable();
            $table->unsignedInteger('vendor_id')->nullable();
            $table->string('vendor_name', 255)->nullable();
            $table->string('invoice_number', 100)->nullable();
            $table->string('challan_number', 100)->nullable();
            $table->date('receiving_date')->nullable();
            $table->integer('receiver_id')->nullable();
            $table->string('receiver_name', 255)->nullable();
            $table->decimal('total_qty', 12, 2)->default(0.00);
            $table->decimal('total_meter', 12, 2)->default(0.00);
            $table->string('invoice_copy_file', 255)->nullable();
            $table->string('packing_slip_file', 255)->nullable();
            $table->string('eway_bill_file', 255)->nullable();
            $table->string('lr_copy_file', 255)->nullable();
            $table->char('financial_year', 4)->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');

            $table->index(['purchase_order_id'], 'purchases_purchase_order_id_index');
            $table->index(['vendor_id'], 'purchases_vendor_id_index');
            $table->index(['invoice_number'], 'purchases_invoice_number_index');
            $table->index(['receiving_date'], 'purchases_receiving_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
