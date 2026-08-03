<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receive_stock_mill_dispatches', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('stock_mill_dispatch_id')->nullable();
            $table->string('invoice_number', 55)->nullable();
            $table->string('vendor_name', 255)->nullable();
            $table->integer('vendor_ind_id')->nullable();
            $table->date('receiving_date')->nullable();
            $table->string('receiver_emp_name', 255)->nullable();
            $table->integer('receiver_emp_ind_id')->nullable();
            $table->boolean('is_pe_completed')->default(false);
            $table->text('bill_front_img')->nullable();
            $table->text('bill_back_img')->nullable();
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
        Schema::dropIfExists('receive_stock_mill_dispatches');
    }
};
