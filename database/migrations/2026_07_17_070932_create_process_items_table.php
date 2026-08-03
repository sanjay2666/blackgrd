<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_items', function (Blueprint $table) {
            $table->id();
            $table->string('entry_name')->nullable();
            $table->string('process_name');
            $table->string('output_name');
            $table->dateTime('created')->nullable();
            $table->dateTime('modified')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->integer('process_sl_no_last');
        }); 
    }

    public function down(): void
    {
        Schema::dropIfExists('process_items');
    }
};