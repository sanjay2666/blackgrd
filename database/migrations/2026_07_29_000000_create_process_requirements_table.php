<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('process_requirements', function (Blueprint $table) {
			$table->increments('id');
			$table->integer('process_type_id');
			$table->integer('item_type_id');
			$table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('process_requirements');
	}
};
