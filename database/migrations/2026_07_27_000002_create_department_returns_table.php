<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_returns', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('work_order_id');
            $table->string('req_lot_number', 255);
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('work_pro_req_id');
            $table->integer('process_type_id')->nullable();
            $table->integer('item_type_id')->nullable();
            $table->date('return_date');
            $table->integer('rejected_by')->nullable();
            $table->tinyText('reject_note')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->enum('is_deleted', ['0', '1'])->default('0');
            $table->text('reason')->nullable();

            $table->char('financial_year', 4)->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('modified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_returns');
    }
};
