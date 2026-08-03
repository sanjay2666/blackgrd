<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_return_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('depart_reqst_id')->nullable();
            $table->bigInteger('work_order_id')->default(0);
            $table->bigInteger('employee_id')->default(0);
            $table->bigInteger('ware_out_item_id')->default(0);
            $table->bigInteger('wis_id')->default(0);
            $table->bigInteger('work_pro_req_id')->default(0);
            $table->bigInteger('item_id')->default(0);
            $table->date('return_date');
            $table->decimal('received_item_qty', 10, 2)->default(0.00);
            $table->decimal('used_item_qty', 10, 2)->default(0.00);
            $table->decimal('item_qty', 10, 2)->default(0.00);
            $table->string('insp_taka_number', 25)->nullable();
            $table->integer('req_lot_number')->nullable();
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');

            $table->char('financial_year', 4)->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('modified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_return_requests');
    }
};
