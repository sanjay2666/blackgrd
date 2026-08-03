<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('greige_receive_stock_item_from_job_works', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('greige_receive_id')->nullable();
            $table->integer('stock_mill_dispatch_item_id');
            $table->integer('received_item_id');
            $table->decimal('received_mtr', 12, 3)->default(0.000);
            $table->integer('used_yarn_id')->nullable();
            $table->decimal('used_yarn_qty', 12, 3)->nullable()->default(0.000);
            $table->integer('used_beam_id')->nullable();
            $table->decimal('used_beam_qty', 12, 3)->nullable()->default(0.000);
            $table->integer('unit_type_id')->nullable();
            $table->string('taka_no', 50)->nullable();
            $table->string('lot_no', 50)->nullable();
            $table->string('remarks', 255)->nullable();

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
        Schema::dropIfExists('greige_receive_stock_item_from_job_works');
    }
};