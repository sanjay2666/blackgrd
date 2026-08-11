<?php

namespace Tests\Unit\GstHsnMaster;

use App\Services\GstHsnMasterService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GstHsnMasterServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('gst_rates', function (Blueprint $table): void {
            $table->increments('gst_rate_id');
            $table->decimal('gst_rate', 6, 2);
            $table->string('description')->nullable();
            $table->dateTime('created')->nullable();
            $table->dateTime('modified')->nullable();
            $table->string('status')->default('Active');
        });
        Schema::create('hsn_codes', function (Blueprint $table): void {
            $table->increments('hsn_code_id');
            $table->string('hsn_code');
            $table->string('description')->nullable();
            $table->unsignedInteger('gst_rate_id')->nullable();
            $table->dateTime('created')->nullable();
            $table->dateTime('modified')->nullable();
            $table->string('status')->default('Active');
        });
        Schema::create('items', function (Blueprint $table): void {
            $table->increments('item_id');
            $table->string('hsncode')->nullable();
        });
    }

    public function test_hsn_is_normalized_and_referenced_rows_cannot_be_deleted(): void
    {
        $service = app(GstHsnMasterService::class);
        $request = Request::create('/admin/hsn-codes', 'POST');
        $hsn = $service->createHsn(['hsn_code' => '  5205   11 ', 'status' => 'Active'], $request);
        $this->assertSame('5205 11', $hsn->hsn_code);
        Schema::getConnection()->table('items')->insert(['hsncode' => $hsn->hsn_code]);
        $this->expectException(ValidationException::class);
        $service->assertCanDelete($hsn);
    }

    public function test_rate_uses_decimal_total_and_status_preserves_reference(): void
    {
        $rate = app(GstHsnMasterService::class)->createRate(['gst_rate' => '18', 'description' => 'Standard', 'status' => 'Active'], Request::create('/admin/gst-rates', 'POST'));
        $this->assertSame('18.00', (string) $rate->gst_rate);
        app(GstHsnMasterService::class)->updateRate($rate, ['gst_rate' => '18.25', 'description' => 'Updated', 'status' => 'Inactive'], Request::create('/admin/gst-rates/1', 'PUT'));
        $this->assertSame('18.25', (string) $rate->fresh()->gst_rate);
        $this->assertSame('Inactive', $rate->fresh()->status);
    }
}
