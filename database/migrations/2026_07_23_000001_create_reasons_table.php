<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reasons', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('reason_from_page', ['cwo', 'wo', 'so', 'soi']);
            $table->integer('sale_order_id')->nullable();
            $table->integer('sale_order_item_id')->nullable();
            $table->integer('work_order_id')->nullable();
            $table->text('reason');
            $table->integer('created_by')->nullable(); 
            $table->integer('modified_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('modified_at')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reasons');
    }
};
