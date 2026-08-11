<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('factory_id')->nullable();
            $table->string('shift_name', 100);
            $table->string('shift_code', 30)->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->text('description')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->dateTime('created')->nullable();
            $table->dateTime('modified')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->index(['company_id', 'factory_id', 'status'], 'shift_scope_status_index');
            $table->index(['company_id', 'start_time'], 'shift_company_start_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
