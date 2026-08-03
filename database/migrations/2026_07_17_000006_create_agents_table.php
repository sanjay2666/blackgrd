<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('individual_id')->default(0);
            $table->string('agent_from');
            $table->string('agent_to');
            $table->integer('created_by');
            $table->integer('modified_by');
            $table->dateTime('created');
            $table->dateTime('modified');
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
