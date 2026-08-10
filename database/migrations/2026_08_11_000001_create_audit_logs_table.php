<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->enum('actor_type', ['Admin', 'User', 'System']);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('guard', 20);
            $table->string('module', 80);
            $table->string('action', 80);
            $table->string('auditable_type', 160)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('event', 120);
            $table->text('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('changed_fields')->nullable();
            $table->string('route_name', 160)->nullable();
            $table->string('http_method', 12)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('request_id', 120)->nullable();
            $table->unsignedBigInteger('financial_year_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('factory_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['actor_type', 'actor_id', 'created_at']);
            $table->index(['module', 'action', 'created_at']);
            $table->index(['auditable_type', 'auditable_id', 'created_at']);
            $table->index('created_at');
            $table->index('financial_year_id');
            $table->foreign('financial_year_id')->references('id')->on('financial_years')->restrictOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->restrictOnDelete();
            $table->foreign('factory_id')->references('id')->on('factories')->restrictOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
