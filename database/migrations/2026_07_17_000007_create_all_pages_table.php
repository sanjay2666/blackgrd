<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('all_pages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('model_name')->nullable();
            $table->string('page_title');
            $table->string('page_name');
            $table->integer('page_rank');
            $table->boolean('status')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('all_pages');
    }
};
