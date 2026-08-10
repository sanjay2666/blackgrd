<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table): void {
                $table->id();
                $table->unsignedInteger('company_id');
                $table->string('branch_code', 30);
                $table->string('name', 150);
                $table->enum('kind', ['head_office', 'commercial', 'other'])->default('other');
                $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->index(['company_id', 'status']);
                $table->unique(['company_id', 'branch_code']);
                $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            });
        }

        if (! Schema::hasTable('factories')) {
            Schema::create('factories', function (Blueprint $table): void {
                $table->id();
                $table->unsignedInteger('company_id');
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->string('factory_code', 30);
                $table->string('name', 150);
                $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->index(['company_id', 'status']);
                $table->index('branch_id');
                $table->unique(['company_id', 'factory_code']);
                $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
                $table->foreign('branch_id')->references('id')->on('branches')->restrictOnDelete();
            });
        }

        if (! Schema::hasTable('user_organization_access')) {
            Schema::create('user_organization_access', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedInteger('company_id');
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->unsignedBigInteger('factory_id')->nullable();
                $table->unsignedBigInteger('department_id')->nullable();
                $table->boolean('is_default')->default(false);
                $table->dateTime('starts_at')->nullable();
                $table->dateTime('ends_at')->nullable();
                $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->index(['user_id', 'company_id', 'status']);
                $table->index(['company_id', 'branch_id', 'factory_id']);
                $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
                $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
                $table->foreign('branch_id')->references('id')->on('branches')->restrictOnDelete();
                $table->foreign('factory_id')->references('id')->on('factories')->restrictOnDelete();
            });
        }

        $this->addScopeColumns('departments', ['company_id' => 'unsignedInteger', 'factory_id' => 'unsignedBigInteger']);
        $this->addScopeColumns('warehouses', ['company_id' => 'unsignedInteger', 'factory_id' => 'unsignedBigInteger']);
        $this->addScopeColumns('machines', ['company_id' => 'unsignedInteger', 'factory_id' => 'unsignedBigInteger', 'department_id' => 'unsignedBigInteger']);
        foreach (['individuals', 'items', 'item_type', 'colours', 'fabric_fault_reasons', 'reasons', 'cotings', 'packaging_types'] as $table) {
            if (Schema::hasTable($table)) {
                $this->addScopeColumns($table, ['company_id' => 'unsignedInteger']);
            }
        }

        foreach ([
            'sale_orders', 'work_orders', 'work_process_requirements', 'work_inspections',
            'gate_passes', 'warehouse_in_items', 'warehouse_out_items', 'warehouse_balance_items',
            'warehouse_item_stocks', 'purchase_orders', 'purchases', 'stock_mill_dispatches',
            'receive_stock_mill_dispatches', 'department_returns', 'department_return_requests',
            'notifications',
        ] as $table) {
            if (Schema::hasTable($table)) {
                $this->addScopeColumns($table, ['company_id' => 'unsignedInteger']);
            }
        }

        $companyId = DB::table('companies')->where('status', 'Active')->orderBy('id')->value('id');
        if ($companyId !== null) {
            foreach (['departments', 'warehouses', 'machines', 'individuals', 'items', 'item_type', 'colours', 'fabric_fault_reasons', 'reasons', 'cotings', 'packaging_types', 'sale_orders', 'work_orders', 'work_process_requirements', 'work_inspections', 'gate_passes', 'warehouse_in_items', 'warehouse_out_items', 'warehouse_balance_items', 'warehouse_item_stocks', 'purchase_orders', 'purchases', 'stock_mill_dispatches', 'receive_stock_mill_dispatches', 'department_returns', 'department_return_requests', 'notifications'] as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'company_id')) {
                    DB::table($table)->whereNull('company_id')->update(['company_id' => $companyId]);
                }
            }

            $now = now();
            $userAccessRows = DB::table('users')->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))->from('user_organization_access as access')
                    ->whereColumn('access.user_id', 'users.id');
            });
            DB::table('user_organization_access')->insertUsing(
                ['user_id', 'company_id', 'is_default', 'status', 'created_at', 'updated_at'],
                $userAccessRows->select('id', DB::raw((int) $companyId), DB::raw('1'), DB::raw("'Active'"), DB::raw("'{$now->format('Y-m-d H:i:s')}'"), DB::raw("'{$now->format('Y-m-d H:i:s')}'"))
            );
        }
    }

    private function addScopeColumns(string $tableName, array $columns): void
    {
        $newColumns = array_filter($columns, fn (string $type, string $column): bool => ! Schema::hasColumn($tableName, $column), ARRAY_FILTER_USE_BOTH);
        if ($newColumns === []) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($newColumns): void {
            foreach ($newColumns as $column => $type) {
                $table->{$type}($column)->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        foreach (['notifications', 'department_return_requests', 'department_returns', 'receive_stock_mill_dispatches', 'stock_mill_dispatches', 'purchases', 'purchase_orders', 'warehouse_item_stocks', 'warehouse_balance_items', 'warehouse_out_items', 'warehouse_in_items', 'gate_passes', 'work_inspections', 'work_process_requirements', 'work_orders', 'sale_orders', 'packaging_types', 'cotings', 'reasons', 'fabric_fault_reasons', 'colours', 'item_type', 'items', 'individuals', 'machines', 'warehouses', 'departments'] as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                    foreach (['company_id', 'factory_id', 'department_id'] as $column) {
                        if (Schema::hasColumn($table, $column)) {
                            $blueprint->dropColumn($column);
                        }
                    }
                });
            }
        }
        Schema::dropIfExists('user_organization_access');
        Schema::dropIfExists('factories');
        Schema::dropIfExists('branches');
    }
};
