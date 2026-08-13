<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    private array $tables = [
        'packaging_orders',
        'packaging_order_items',
        'packaging_roll_allocations',
        'sales_challan_items',
        'sales_challan_roll_allocations',
        'production_genealogy_links',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->unsignedBigInteger('financial_year_id')->nullable();
                $table->index('financial_year_id', $tableName.'_financial_year_index');
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->dropIndex($tableName.'_financial_year_index');
                $table->dropColumn('financial_year_id');
            });
        }
    }
};
