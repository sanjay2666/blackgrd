<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gate_pass_print_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('gate_pass_id');
            $table->integer('user_id');
            $table->string('printed_from', 255);
            $table->timestamp('printed_at')->useCurrent();
            $table->integer('print_count');

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
        Schema::dropIfExists('gate_pass_print_logs');
    }
};
