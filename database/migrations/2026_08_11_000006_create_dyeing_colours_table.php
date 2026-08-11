<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('dyeing_colours', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('company_id');
            $table->unsignedBigInteger('colour_id');
            $table->string('name');
            $table->string('code', 100)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->dateTime('created')->nullable();
            $table->dateTime('modified')->nullable();
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'colour_id', 'status']);
            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('colour_id')->references('id')->on('colours')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dyeing_colours');
    }
};
