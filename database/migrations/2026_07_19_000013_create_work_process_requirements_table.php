<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('work_process_requirements', function (Blueprint $table) {
			$table->increments('id');
			$table->integer('work_order_id')->nullable();
			$table->integer('warehouse_balance_item_id')->nullable();
			$table->integer('wis_id')->nullable();
			$table->integer('item_id')->nullable();
			$table->integer('process_type_id')->nullable();
			$table->integer('item_type_id')->nullable();
			$table->integer('unit_type_id')->nullable();
			$table->boolean('req_fabric_type')->default(1)->comment('1=Greige, 2=Dyed, 3=Coated');
			$table->integer('work_req_send_by')->nullable();
			$table->string('req_lot_no', 22)->nullable();
			$table->decimal('quantity', 10, 2)->nullable();
			$table->decimal('alloted_quantity', 10, 2)->default(0.00);
			$table->decimal('return_quantity', 10, 2)->default(0.00);
			$table->string('dyeing_color', 255)->nullable();
			$table->string('coating_type', 255)->nullable();
			$table->string('print_job', 255)->nullable();
			$table->string('extra_job', 255)->nullable();
			$table->enum('is_pro_acc_by_warehouse', ['Yes', 'No'])->nullable();
			
			$table->integer('process_accepted_by')->nullable();
			$table->integer('process_deny_by')->nullable();
			$table->date('acc_deny_date')->nullable();
			$table->tinyInteger('is_accept')->default(0)->comment('0=Pending, 1=Accepted, 2=Denied');
			$table->tinyText('alloted_remark')->nullable();
			$table->text('dept_req_ids')->nullable();
			$table->enum('is_jw_generated_by_warehouse', ['Yes', 'No'])->default('No');
			$table->integer('tot_genrate_jw')->nullable();
			$table->enum('is_lab_test_complete', ['Yes', 'No'])->default('No');
			$table->enum('lab_req_status', ['Pending', 'Requested', 'Approved', 'Rejected'])->default('Pending');
			$table->integer('dyeing_machine_id')->nullable();
			$table->dateTime('dye_m_set_date')->nullable(); 
			$table->enum('insp_status', ['Pending', 'Complete'])->default('Pending');
			$table->enum('is_item_returned', ['Yes', 'No'])->default('No');
			$table->enum('is_all_item_returned', ['Yes', 'No'])->default('No');
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
		Schema::dropIfExists('work_process_requirements');
	}
};