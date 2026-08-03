<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gate_passes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('inspection_id')->nullable();
            $table->integer('work_order_id')->nullable();
            $table->integer('sale_order_item_id')->nullable();
            $table->integer('item_id')->nullable();
            $table->integer('item_type_id')->nullable();
            $table->integer('unit_type_id')->nullable();
            $table->integer('qty')->nullable();
            $table->integer('qty_size')->nullable();
            $table->decimal('insp_beam_meter', 10, 2)->nullable();
            $table->string('to_department', 55)->nullable();
            $table->string('to_warehouse', 55)->nullable();
            $table->string('dyeing_color', 255)->nullable();
            $table->string('coated_pvc', 255)->nullable();
            $table->string('extra_job', 255)->nullable();
            $table->string('print_job', 255)->nullable();
            $table->string('gatepass_number', 55)->nullable();
            $table->string('insp_taka_number', 22)->nullable();
            $table->string('dyeing_lot_number', 22)->nullable();
            $table->string('dyeing_taka_number', 22)->nullable();
            $table->integer('fabric_fault_reason_id')->nullable();
            $table->integer('genrated_by')->nullable();
            $table->enum('is_item_received_in_warehouse', ['Yes', 'No'])->default('No');
            $table->date('print_date')->nullable();
            $table->integer('print_count')->default(0);
            $table->tinyText('inspec_comment');
            $table->integer('deleted_by')->nullable()->default(0);
            $table->boolean('is_deleted')->nullable()->default(0);

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
        Schema::dropIfExists('gate_passes');
    }
};
