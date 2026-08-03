<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_type', function (Blueprint $table) {
            $table->increments('item_type_id');
            $table->string('item_type_name');
            $table->integer('unit_type_id')->nullable();
            $table->enum('is_purchase', ['1', '0'])->default('0')->comment('0=no,1=yes');
            $table->enum('is_work', ['1', '0'])->default('0');
            $table->enum('is_department', ['0', '1'])->default('0');
            $table->dateTime('created');
            $table->dateTime('modified');
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_type');
    }
};
