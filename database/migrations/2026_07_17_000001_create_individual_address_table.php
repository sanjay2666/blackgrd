<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('individual_address', function (Blueprint $table) {
            $table->increments('ind_add_id');
            $table->integer('individual_id');
            $table->enum('address_type', ['s', 'b'])->default('b');
            $table->string('address_1', 5555);
            $table->string('address_2', 5555);
            $table->integer('state_id');
            $table->string('city', 255);
            $table->string('zip_code', 10);
            $table->boolean('default_address')->default(false);
            $table->dateTime('created');
            $table->dateTime('modified_at')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
        }); 
		
    }

    public function down(): void
    {
        Schema::dropIfExists('individual_address');
    }
};