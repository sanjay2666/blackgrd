<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_yarn_requirements', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('item_id');
            $table->integer('yarn_id');
            $table->integer('reed_peak');
            $table->decimal('yarn_quantity', 10, 2)->nullable();
            $table->string('unit', 22);
            $table->integer('process_id');

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
        Schema::dropIfExists('item_yarn_requirements');
    }
};