<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->engine = 'MyISAM';
            $table->charset = 'utf8';
			$table->collation = 'utf8_unicode_ci';

            $table->increments('id');
            $table->unsignedInteger('vendor_id')->nullable();
            $table->integer('billing_id')->nullable();
            $table->integer('shiping_id')->nullable();
            $table->text('billing_address')->nullable();
            $table->text('shiping_address')->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->decimal('subtotal', 10, 2)->nullable();
            $table->decimal('frieght', 10, 2)->nullable();
            $table->text('order_remark')->nullable();
            $table->timestamp('purchase_started')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->decimal('sgstrs', 10, 2)->nullable();
            $table->decimal('cgstrs', 10, 2)->nullable();
            $table->decimal('igstrs', 10, 2)->nullable();
            $table->decimal('cess', 10, 2)->nullable();
            $table->decimal('cessrs', 10, 2)->nullable();
            $table->decimal('taxrs', 10, 2)->nullable();			
            $table->dateTime('purchased_on')->nullable();
            $table->enum('is_all_item_received', ['Yes', 'No'])->default('No');
            $table->enum('is_item_received_in_warehouse', ['Yes', 'No'])->default('No');
            $table->enum('is_deleted', ['Yes', 'No'])->default('No');
            $table->enum('is_return', ['Yes', 'No'])->default('No');
            $table->integer('canceled_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->char('financial_year', 4)->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('modified_at')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
