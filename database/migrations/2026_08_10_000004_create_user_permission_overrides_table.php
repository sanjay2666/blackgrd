<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_permission_overrides', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('permission_id');
            $table->enum('effect', ['Allow', 'Deny']);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->unsignedBigInteger('revoked_by')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'permission_id']);
            $table->index(['user_id', 'status']);
            $table->index(['permission_id', 'effect', 'status']);
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('permission_id')->references('id')->on('permissions')->restrictOnDelete();
            $table->foreign('assigned_by')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('revoked_by')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permission_overrides');
    }
};
