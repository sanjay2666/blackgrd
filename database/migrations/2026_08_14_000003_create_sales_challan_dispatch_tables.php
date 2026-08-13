<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_challans', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('company_id')->index();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->unsignedInteger('customer_id')->index();
            $table->unsignedBigInteger('financial_year_id')->nullable()->index();
            $table->string('challan_number', 80);
            $table->string('status', 30)->default('Draft')->index();
            $table->date('challan_date');
            $table->date('dispatch_date')->nullable();
            $table->string('customer_name', 255);
            $table->string('customer_gstin', 20)->nullable();
            $table->string('customer_phone', 25)->nullable();
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->unsignedInteger('transporter_id')->nullable()->index();
            $table->string('transporter_name', 255)->nullable();
            $table->string('transporter_phone', 25)->nullable();
            $table->string('transporter_email', 100)->nullable();
            $table->string('transporter_gstin', 20)->nullable();
            $table->string('from_station', 100)->nullable();
            $table->string('to_station', 100)->nullable();
            $table->string('lr_number', 100)->nullable();
            $table->date('lr_date')->nullable();
            $table->string('vehicle_number', 50)->nullable();
            $table->string('driver_name', 100)->nullable();
            $table->string('driver_contact', 25)->nullable();
            $table->unsignedInteger('parcel_count')->nullable();
            $table->unsignedInteger('roll_count')->default(0);
            $table->unsignedInteger('lot_count')->default(0);
            $table->decimal('total_meter', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->uuid('submission_key');
            $table->unsignedInteger('print_count')->default(0);
            $table->dateTime('first_printed_at')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('posted_by')->nullable();
            $table->dateTime('posted_at')->nullable();
            $table->unsignedInteger('cancelled_by')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->string('cancellation_reason', 1000)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->enum('record_status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->unique(['company_id', 'challan_number'], 'sales_challan_company_number_unique');
            $table->unique(['company_id', 'submission_key'], 'sales_challan_company_submission_unique');
            $table->index(['company_id', 'customer_id', 'status'], 'sales_challan_company_customer_status_index');
        });

        Schema::create('sales_challan_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('company_id')->index();
            $table->unsignedBigInteger('sales_challan_id')->index();
            $table->unsignedBigInteger('packaging_order_id')->index();
            $table->unsignedBigInteger('packaging_order_item_id')->index();
            $table->unsignedInteger('sale_order_id')->nullable()->index();
            $table->unsignedInteger('sale_order_item_id')->index();
            $table->string('sale_order_number', 100)->nullable();
            $table->string('customer_po_reference', 100)->nullable();
            $table->unsignedInteger('item_id')->nullable();
            $table->unsignedInteger('item_type_id')->nullable();
            $table->unsignedInteger('unit_type_id')->nullable();
            $table->unsignedInteger('packaging_type_id')->nullable();
            $table->string('packaging_type_name', 100)->nullable();
            $table->string('item_name', 255)->nullable();
            $table->string('grey_quality', 555)->nullable();
            $table->string('dyeing_color', 555)->nullable();
            $table->string('coating_type', 555)->nullable();
            $table->string('print_job', 555)->nullable();
            $table->text('extra_job')->nullable();
            $table->string('final_dispatch_width', 255)->nullable();
            $table->string('tube_width', 55)->nullable();
            $table->decimal('dispatched_quantity', 12, 2)->default(0);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->enum('record_status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->unique(['sales_challan_id', 'packaging_order_item_id'], 'sales_challan_item_packaging_source_unique');
        });

        Schema::create('sales_challan_roll_allocations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('company_id')->index('scr_company_idx');
            $table->unsignedBigInteger('sales_challan_id')->index('scr_challan_idx');
            $table->unsignedBigInteger('sales_challan_item_id')->index('scr_item_idx');
            $table->unsignedBigInteger('packaging_order_id')->index('scr_pack_order_idx');
            $table->unsignedBigInteger('packaging_order_item_id')->index('scr_pack_item_idx');
            $table->unsignedBigInteger('packaging_roll_allocation_id')->index('scr_pack_roll_idx');
            $table->unsignedBigInteger('warehouse_item_stock_id')->index('scr_wis_idx');
            $table->unsignedInteger('warehouse_out_item_id')->nullable()->index('scr_woi_idx');
            $table->string('dyeing_lot_number', 50)->nullable();
            $table->string('packet_number', 50)->nullable();
            $table->string('insp_taka_number', 50)->nullable();
            $table->decimal('packed_quantity_snapshot', 12, 2)->default(0);
            $table->decimal('previously_dispatched_quantity_snapshot', 12, 2)->default(0);
            $table->decimal('dispatched_quantity', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->enum('record_status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->unique(['sales_challan_id', 'packaging_roll_allocation_id'], 'sales_challan_roll_source_unique');
            $table->index(['company_id', 'packaging_roll_allocation_id', 'record_status'], 'sales_challan_roll_source_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_challan_roll_allocations');
        Schema::dropIfExists('sales_challan_items');
        Schema::dropIfExists('sales_challans');
    }
};
