<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('couriers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('cus_id')->nullable();
            $table->string('cus_name')->nullable();
            $table->string('phone', 22)->nullable();
            $table->string('whatsapp', 22)->nullable();
            $table->integer('item_id')->nullable();
            $table->string('item_name')->nullable();
            $table->string('tot_mtr', 5555)->nullable();
            $table->integer('tot_pack')->nullable();
            $table->string('courier_name')->nullable();
            $table->string('tracking_number', 2555)->nullable();
            $table->string('track_url', 555)->nullable();
            $table->integer('changes_track_by')->nullable();
            $table->enum('is_msg_send', ['Yes', 'No'])->default('No');
            $table->enum('is_track_msg_send', ['Yes', 'No'])->default('No');
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->integer('is_deleted')->nullable();
            $table->dateTime('created');
            $table->dateTime('modified');
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('couriers');
    }
};
