<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->integer('process_type_id')->nullable();
            $table->integer('user_id');
            $table->integer('emp_id')->nullable();
            $table->string('model_name', 555);
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('ref_table', 100)->nullable();
            $table->string('notification_type', 100)->nullable();
            $table->string('title')->nullable();
            $table->text('page_link');
            $table->text('message');
            $table->string('page_name', 555);
            $table->string('ip_address', 22);
            $table->text('server_details');
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->dateTime('created')->nullable();
            $table->dateTime('modified')->nullable();
            $table->boolean('is_read')->default(false)->comment('0=Unread,1=Read');
            $table->dateTime('read_at')->nullable();

            $table->index(
                ['process_type_id', 'user_id', 'emp_id'],
                'process_type_id'
            );
            $table->index(
                ['user_id', 'is_read', 'status', 'created'],
                'idx_user_read_status_created'
            );
            $table->index(
                ['ref_table', 'ref_id'],
                'idx_ref_table_ref_id'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
