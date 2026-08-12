<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('process_item_configurations', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('company_id');
            $table->unsignedBigInteger('process_item_id');
            $table->enum('execution_mode', ['Internal', 'External', 'Both'])->default('Both');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique('process_item_id');
            $table->index(['company_id', 'execution_mode']);
            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('process_item_id')->references('id')->on('process_items')->restrictOnDelete();
        });

        Schema::create('process_item_material_configurations', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('company_id');
            $table->unsignedBigInteger('process_item_id');
            $table->unsignedInteger('item_type_id');
            $table->enum('direction', ['Input', 'Output']);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique(['process_item_id', 'item_type_id', 'direction'], 'process_item_material_direction_unique');
            $table->index(['company_id', 'direction']);
            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('process_item_id')->references('id')->on('process_items')->restrictOnDelete();
            $table->foreign('item_type_id')->references('item_type_id')->on('item_type')->restrictOnDelete();
        });

        Schema::create('process_item_allowed_next', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('company_id');
            $table->unsignedBigInteger('process_item_id');
            $table->unsignedBigInteger('next_process_item_id');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique(['process_item_id', 'next_process_item_id'], 'process_item_allowed_next_unique');
            $table->index(['company_id', 'next_process_item_id']);
            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('process_item_id')->references('id')->on('process_items')->restrictOnDelete();
            $table->foreign('next_process_item_id')->references('id')->on('process_items')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_item_allowed_next');
        Schema::dropIfExists('process_item_material_configurations');
        Schema::dropIfExists('process_item_configurations');
    }
};
