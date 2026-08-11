<?php

namespace Tests\Unit\UnitMaster;

use App\Enums\RecordStatus;
use App\Models\UnitType;
use App\Services\UnitTypeService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UnitTypeServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('unit_type', function (Blueprint $table): void {
            $table->increments('unit_type_id');
            $table->string('unit_type_name');
            $table->string('unit_code')->nullable();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('decimal_places')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->dateTime('created')->nullable();
            $table->dateTime('modified')->nullable();
            $table->string('status')->default('Active');
        });
        Schema::create('items', function (Blueprint $table): void {
            $table->increments('item_id');
            $table->unsignedInteger('unit_type_id')->nullable();
        });
    }

    public function test_new_unit_preserves_identity_fields_and_status(): void
    {
        $unit = app(UnitTypeService::class)->create([
            'unit_type_name' => 'Kilogram', 'unit_code' => 'kg', 'decimal_places' => 3,
            'description' => 'Mass measurement', 'status' => 'Active',
        ], Request::create('/admin/unit-types', 'POST'));

        $this->assertSame('Kilogram', $unit->unit_type_name);
        $this->assertSame('KG', $unit->unit_code);
        $this->assertSame(3, $unit->decimal_places);
        $this->assertSame('Active', (string) $unit->status);
    }

    public function test_referenced_units_cannot_be_deleted(): void
    {
        $unit = UnitType::create(['unit_type_name' => 'Meter', 'status' => 'Active', 'created' => now(), 'modified' => now()]);
        DB::table('items')->insert(['unit_type_id' => $unit->getKey()]);

        $this->expectException(ValidationException::class);
        app(UnitTypeService::class)->assertCanDelete($unit);
    }

    public function test_protected_legacy_identity_cannot_be_renamed(): void
    {
        $unit = UnitType::create(['unit_type_id' => 2, 'unit_type_name' => 'Meter', 'status' => 'Active', 'created' => now(), 'modified' => now()]);

        $this->expectException(ValidationException::class);
        app(UnitTypeService::class)->update($unit, ['unit_type_name' => 'Piece'], Request::create('/admin/unit-types/2', 'PUT'));
    }

    public function test_deactivation_keeps_historical_reference_and_inactive_is_not_active(): void
    {
        $unit = UnitType::create(['unit_type_name' => 'Roll', 'status' => 'Active', 'created' => now(), 'modified' => now()]);
        DB::table('items')->insert(['unit_type_id' => $unit->getKey()]);
        app(UnitTypeService::class)->setStatus($unit, RecordStatus::Inactive, Request::create('/admin/unit-types/1/deactivate', 'PATCH'));

        $this->assertSame(1, DB::table('items')->where('unit_type_id', $unit->getKey())->count());
        $this->assertFalse(UnitType::active()->whereKey($unit->getKey())->exists());
        $this->assertTrue(UnitType::inactive()->whereKey($unit->getKey())->exists());
    }
}
