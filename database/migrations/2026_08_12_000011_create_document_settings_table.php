<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('document_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('company_id');
            $table->string('document_key', 50);
            $table->boolean('show_logo')->default(true);
            $table->boolean('show_company_address')->default(true);
            $table->boolean('show_company_tax_identity')->default(true);
            $table->string('document_title', 150)->nullable();
            $table->text('footer_text')->nullable();
            $table->text('terms_text')->nullable();
            $table->string('signatory_label', 100)->nullable();
            $table->string('copy_label_primary', 100)->nullable();
            $table->string('copy_label_secondary', 100)->nullable();
            $table->string('copy_label_tertiary', 100)->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('modified_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('modified_at')->nullable();
            $table->unique(['company_id', 'document_key'], 'document_settings_company_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_settings');
    }
};
