<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_series', function (Blueprint $table): void {
            $table->id();
            $table->string('series_key', 100);
            $table->string('document_name', 160);
            $table->string('prefix', 30)->default('');
            $table->string('suffix', 30)->nullable();
            $table->unsignedInteger('padding')->default(0);
            $table->unsignedBigInteger('next_number')->default(1);
            $table->enum('reset_policy', ['never', 'financial_year'])->default('never');
            $table->boolean('financial_year_aware')->default(false);
            $table->unsignedBigInteger('financial_year_id')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamps();
            $table->unique(['series_key', 'financial_year_id'], 'number_series_key_year_unique');
            $table->index(['status', 'financial_year_id']);
            $table->foreign('financial_year_id')->references('id')->on('financial_years')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_series');
    }
};
