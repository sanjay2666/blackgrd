<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('workflow_definitions', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('company_id');
            $table->string('workflow_code', 80);
            $table->string('workflow_name', 255);
            $table->text('description')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('modified_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique(['company_id', 'workflow_code']);
            $table->index(['company_id', 'status']);
            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
        });

        Schema::create('workflow_versions', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('workflow_definition_id');
            $table->unsignedInteger('version_number');
            $table->enum('status', ['Draft', 'Finalized'])->default('Draft');
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('finalized_by')->nullable();
            $table->dateTime('finalized_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique(['workflow_definition_id', 'version_number']);
            $table->index(['company_id', 'status']);
            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('workflow_definition_id')->references('id')->on('workflow_definitions')->restrictOnDelete();
        });

        Schema::create('workflow_version_steps', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('workflow_version_id');
            $table->unsignedBigInteger('process_id');
            $table->unsignedInteger('sequence');
            $table->string('step_label', 255)->nullable();
            $table->text('description')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->unique(['workflow_version_id', 'sequence']);
            $table->index(['company_id', 'process_id']);
            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('workflow_version_id')->references('id')->on('workflow_versions')->restrictOnDelete();
            $table->foreign('process_id')->references('id')->on('process_items')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_version_steps');
        Schema::dropIfExists('workflow_versions');
        Schema::dropIfExists('workflow_definitions');
    }
};
