<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('warehouse_name', 255);
            $table->string('location', 255)->nullable();
            $table->decimal('capacity', 12, 2)->nullable();
            $table->unsignedBigInteger('supervisor_id')->nullable();
            $table->string('contact_number', 20)->nullable();
            $table->unsignedInteger('process_type_id')->default(0);
            $table->char('financial_year', 4)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
