<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_type', function (Blueprint $table) {
            $table->increments('unit_type_id');
            $table->string('unit_type_name');
            $table->dateTime('created');
            $table->dateTime('modified');
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_type');
    }
};
