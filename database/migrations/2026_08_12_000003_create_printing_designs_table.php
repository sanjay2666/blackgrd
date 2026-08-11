<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('printing_designs')) {
            return;
        }

        Schema::create('printing_designs', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('company_id')->nullable();
            $table->string('design_name', 255);
            $table->string('design_code', 100)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('display_order')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index(['company_id', 'status'], 'printing_designs_company_status_idx');
            $table->index(['company_id', 'display_order'], 'printing_designs_company_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('printing_designs');
    }
};
