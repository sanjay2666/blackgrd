<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('hsn_codes', function (Blueprint $table): void {
            $table->increments('hsn_code_id');
            $table->string('hsn_code', 40);
            $table->string('description', 1000)->nullable();
            $table->unsignedInteger('gst_rate_id')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->dateTime('created')->useCurrent();
            $table->dateTime('modified')->nullable();
            $table->index(['hsn_code', 'status']);
            $table->foreign('gst_rate_id')->references('gst_rate_id')->on('gst_rates')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hsn_codes');
    }
};
