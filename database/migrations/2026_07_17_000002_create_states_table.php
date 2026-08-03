<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('states', function (Blueprint $table) {
            $table->increments('id');

            $table->string('name', 30);
            $table->integer('country_id')->default(1);

            $table->dateTime('created')->nullable();
            $table->dateTime('modified')->nullable();

            $table->enum('status', [
                'Active',
                'Inactive',
                'Deleted'
            ])->default('Active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('states');
    }
};