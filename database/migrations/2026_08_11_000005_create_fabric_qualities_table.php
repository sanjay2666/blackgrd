<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('fabric_qualities')) {
            return;
        }

        Schema::create('fabric_qualities', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('company_id')->nullable();
            $table->string('quality_name', 255);
            $table->string('quality_code', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('gsm', 25)->nullable();
            $table->string('width', 22)->nullable();
            $table->unsignedInteger('display_order')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index(['company_id', 'status'], 'fabric_qualities_company_status_idx');
            $table->index(['company_id', 'display_order'], 'fabric_qualities_company_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fabric_qualities');
    }
};
