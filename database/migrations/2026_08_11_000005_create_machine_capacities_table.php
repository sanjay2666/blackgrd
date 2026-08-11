<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('machine_capacities', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('machine_id');
            $table->unsignedInteger('unit_type_id');
            $table->decimal('capacity_value', 12, 3);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->dateTime('created')->nullable();
            $table->dateTime('modified')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->index(['company_id', 'machine_id', 'status'], 'machine_capacity_machine_status_index');
            $table->index(['company_id', 'unit_type_id'], 'machine_capacity_unit_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_capacities');
    }
};
