<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('company_id')->nullable();
            $table->string('role_key', 120)->unique();
            $table->string('name', 120);
            $table->enum('scope', ['System', 'Company']);
            $table->enum('panel', ['Admin', 'Frontend'])->default('Frontend');
            $table->text('description')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'name']);
            $table->index(['scope', 'status']);
            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('permission_key', 120)->unique();
            $table->string('resource', 60)->index();
            $table->string('action', 60);
            $table->string('category', 40)->index();
            $table->string('description')->nullable();
            $table->boolean('is_critical')->default(false);
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamps();
            $table->primary(['role_id', 'permission_id']);
            $table->index(['permission_id', 'role_id']);
            $table->foreign('role_id')->references('id')->on('roles')->restrictOnDelete();
            $table->foreign('permission_id')->references('id')->on('permissions')->restrictOnDelete();
            $table->foreign('assigned_by')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('user_role_assignments', function (Blueprint $table): void {
            $table->id();
            $table->enum('principal_type', ['Admin', 'User']);
            $table->unsignedBigInteger('principal_id');
            $table->unsignedBigInteger('role_id');
            $table->unsignedInteger('company_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('factory_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->unsignedBigInteger('revoked_by')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['principal_type', 'principal_id', 'status']);
            $table->index(['company_id', 'status']);
            $table->index(['role_id', 'status']);
            $table->index(['company_id', 'branch_id', 'factory_id', 'department_id'], 'ura_company_unit_scope_idx');
            $table->foreign('role_id')->references('id')->on('roles')->restrictOnDelete();
            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->restrictOnDelete();
            $table->foreign('factory_id')->references('id')->on('factories')->restrictOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->restrictOnDelete();
            $table->foreign('assigned_by')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('revoked_by')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_role_assignments');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
