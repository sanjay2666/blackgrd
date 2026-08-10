<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        foreach (['branches', 'factories'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('address', 255)->nullable();
                $table->string('city', 100)->nullable();
                $table->string('state', 100)->nullable();
                $table->string('pin_code', 20)->nullable();
                $table->string('country', 100)->nullable();
                $table->string('phone', 30)->nullable();
                $table->string('mobile', 30)->nullable();
                $table->string('email', 150)->nullable();
                $table->string('contact_person', 150)->nullable();
                $table->string('gstin', 15)->nullable();
                $table->text('remarks')->nullable();
                $table->index('city');
            });
        }
    }

    public function down(): void
    {
        // The additive columns are retained on rollback to avoid destructive schema loss.
    }
};
