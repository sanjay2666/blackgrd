<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('individuals', function (Blueprint $table) {
            $table->id();
            $table->integer('process_type_id')->nullable()->comment('comes from process_items table');
            $table->string('name')->nullable();
            $table->enum('type', ['customers', 'master', 'agents', 'labourer', 'vendors', 'transport', 'employee'])->default('customers');
            $table->enum('vendor_type', ['yarn', 'greige', 'chemical', 'maintanance', 'general'])->nullable();
            $table->string('phone', 25)->nullable();
            $table->string('company_name', 100)->nullable();
            $table->string('nick_name')->nullable();
            $table->string('gstin', 100)->nullable();
            $table->string('pan', 100)->nullable();
            $table->string('tanno', 11)->nullable();
            $table->string('adhar', 100)->nullable();
            $table->string('whatsapp', 100)->nullable();
            $table->string('email', 100)->nullable();
            $table->enum('is_lab_test_required', ['Yes', 'No'])->default('No');
            $table->string('verified_remark', 100)->nullable();
            $table->enum('is_verified', ['yes', 'no'])->default('no');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('modified_at')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('individuals');
    }
};