<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->increments('id');

            /*
            |--------------------------------------------------------------------------
            | Basic Company Details
            |--------------------------------------------------------------------------
            */
            $table->string('company_code', 30)->nullable();
            $table->string('name', 150);
            $table->string('legal_name', 200)->nullable();
            $table->string('trade_name', 200)->nullable();
            $table->text('company_description')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Registration Details
            |--------------------------------------------------------------------------
            */
            $table->string('registration_no', 100)->nullable();

            $table->string('pan_no', 20)->nullable();
            $table->string('tan_no', 20)->nullable();
            $table->string('gstin', 22)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Contact Details
            |--------------------------------------------------------------------------
            */
            $table->string('email', 150)->nullable();
            $table->string('alternate_email', 150)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('alternate_phone', 20)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('whatsapp_no', 20)->nullable();
            $table->string('website', 255)->nullable();

            $table->string('contact_person_name', 150)->nullable();
            $table->string('contact_person_designation', 100)->nullable();
            $table->string('contact_person_mobile', 20)->nullable();
            $table->string('contact_person_email', 150)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Registered Address
            |--------------------------------------------------------------------------
            */
            $table->string('address_1', 555)->nullable();
            $table->string('address_2', 555)->nullable();
            $table->string('landmark', 255)->nullable();
            $table->unsignedInteger('country_id')->nullable();
            $table->unsignedInteger('state_id')->nullable();
            $table->string('state_name', 100)->nullable();
            $table->string('city_name', 100)->nullable();
            $table->string('district_name', 100)->nullable();
            $table->string('pincode', 10)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Billing Address
            |--------------------------------------------------------------------------
            */
            $table->string('billing_address_1', 555)->nullable();
            $table->string('billing_address_2', 555)->nullable();
            $table->unsignedInteger('billing_country_id')->nullable();
            $table->unsignedInteger('billing_state_id')->nullable();
            $table->string('billing_state_name', 100)->nullable();
            $table->string('billing_city_name', 100)->nullable();
            $table->string('billing_pincode', 10)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Bank Details
            |--------------------------------------------------------------------------
            */
            $table->string('bank_name', 150)->nullable();
            $table->string('bank_branch_name', 150)->nullable();
            $table->string('bank_account_holder_name', 150)->nullable();
            $table->string('bank_account_no', 50)->nullable();
            $table->string('bank_account_type', 30)->nullable();
            $table->string('bank_ifsc_code', 20)->nullable();
            $table->string('bank_micr_code', 20)->nullable();
            $table->string('bank_swift_code', 20)->nullable();
            $table->string('bank_upi_id', 100)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Invoice And Accounting Settings
            |--------------------------------------------------------------------------
            */
            $table->string('invoice_prefix', 20)->nullable();
            $table->string('quotation_prefix', 20)->nullable();
            $table->string('purchase_prefix', 20)->nullable();
            $table->string('currency_code', 10)->default('INR');
            $table->string('currency_symbol', 10)->default('Rs');
            $table->string('timezone', 100)->default('Asia/Kolkata');
            $table->string('date_format', 20)->default('d-m-Y');
            $table->tinyInteger('decimal_places')->default(2);
            $table->decimal('default_tax_percentage', 8, 2)->default(0.00);
            $table->decimal('credit_limit', 15, 2)->default(0.00);
            $table->integer('default_credit_days')->default(0);
            $table->text('invoice_terms')->nullable();
            $table->text('invoice_footer')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Company Files
            |--------------------------------------------------------------------------
            */
            $table->string('logo', 255)->nullable();
            $table->string('favicon', 255)->nullable();
            $table->string('letterhead', 255)->nullable();
            $table->string('signature_image', 255)->nullable();
            $table->string('company_stamp', 255)->nullable();
            $table->string('qr_code_image', 255)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Additional Details
            |--------------------------------------------------------------------------
            */
            $table->string('latitude', 50)->nullable();
            $table->string('longitude', 50)->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->text('remarks')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Financial Year And Audit Details
            |--------------------------------------------------------------------------
            */

            $table->integer('created_by')->nullable();
            $table->integer('modified_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->enum('status', [
                'Active',
                'Inactive',
                'Deleted',
            ])->default('Active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
