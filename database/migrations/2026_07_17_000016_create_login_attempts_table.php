<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable();
            $table->text('password_enc')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('ip2', 45)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('postal', 20)->nullable();
            $table->decimal('lat', 10, 6)->nullable();
            $table->decimal('lon', 10, 6)->nullable();
            $table->string('isp')->nullable();
            $table->string('asn', 50)->nullable();
            $table->string('geo_source', 30)->nullable();
            $table->string('location')->nullable();
            $table->string('device')->nullable();
            $table->text('url')->nullable();
            $table->text('referrer')->nullable();
            $table->text('browser')->nullable();
            $table->text('platform')->nullable();
            $table->enum('status', ['attempt', 'success', 'failed'])->default('attempt');
            $table->timestamps();
            $table->index('status', 'idx_login_attempts_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
