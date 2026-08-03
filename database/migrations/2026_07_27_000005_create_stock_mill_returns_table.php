<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_mill_returns', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('vendor_id')->nullable();
            $table->integer('ind_add_id')->nullable();
            $table->integer('ind_add_id_ship')->nullable();
            $table->string('work_name', 555)->nullable();
            $table->text('mill_address')->nullable();
            $table->string('gst_number', 55)->nullable();
            $table->string('item_name', 255)->nullable();
            $table->integer('item_type_id')->nullable();
            $table->integer('item_id')->nullable();
            $table->string('voucher_number', 11)->nullable();
            $table->string('chalan_no', 11)->nullable();
            $table->date('chalan_date')->nullable();
            $table->integer('process_type')->nullable();
            $table->enum('work_type_id', ['dyeing', 'coating', 'printing', 'extra'])->default('dyeing');
            $table->integer('work_order_id')->nullable();
            $table->integer('state')->nullable();
            $table->string('vendor_name', 255)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->decimal('total_pcs', 10, 2)->nullable();
            $table->decimal('tot_meter', 10, 2)->nullable();
            $table->decimal('tot_receive_mtr', 10, 2)->default(0.00);
            $table->boolean('is_tot_mtr_received')->default(false);
            $table->text('remark')->nullable();
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
        Schema::dropIfExists('stock_mill_returns');
    }
};
