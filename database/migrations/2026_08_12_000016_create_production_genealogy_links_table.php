<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('production_genealogy_links', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('company_id');
            $table->string('event_type', 32);
            $table->string('relationship_type', 32);
            $table->string('source_type', 24);
            $table->string('source_table', 64);
            $table->unsignedBigInteger('source_id');
            $table->string('source_identity', 100);
            $table->string('result_type', 24);
            $table->string('result_table', 64);
            $table->unsignedBigInteger('result_id');
            $table->string('result_identity', 100);
            $table->decimal('quantity', 12, 2)->nullable();
            $table->unsignedBigInteger('work_order_id')->nullable();
            $table->unsignedBigInteger('work_process_requirement_id')->nullable();
            $table->unsignedBigInteger('work_inspection_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->unique(
                ['event_type', 'source_table', 'source_id', 'result_table', 'result_id'],
                'production_genealogy_operation_unique'
            );
            $table->index(['company_id', 'source_type', 'source_identity'], 'production_genealogy_source_lookup');
            $table->index(['company_id', 'result_type', 'result_identity'], 'production_genealogy_result_lookup');
            $table->index(['company_id', 'work_order_id'], 'production_genealogy_work_order_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_genealogy_links');
    }
};
