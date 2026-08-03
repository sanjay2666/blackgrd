<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machines', function (Blueprint $table) {
            $table->id();

            $table->string('name')->nullable();
            $table->string('process_wise')->nullable();

            $table->enum('is_busy', ['1', '0'])->nullable();

            $table->dateTime('created')->nullable();
            $table->dateTime('modified')->nullable();

            $table->char('financial_year', 4)->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->enum('status', [
                'Active',
                'Inactive',
                'Deleted'
            ])->default('Active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machines');
    }
};