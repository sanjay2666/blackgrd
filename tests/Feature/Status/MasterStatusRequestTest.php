<?php

namespace Tests\Feature\Status;

use App\Exceptions\InvalidRecordStatusTransition;
use App\Http\Middleware\EnforceFrontendPagePermission;
use App\Models\UnitType;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MasterStatusRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(EnforceFrontendPagePermission::class);

        Schema::create('unit_type', function (Blueprint $table): void {
            $table->increments('unit_type_id');
            $table->string('unit_type_name');
            $table->dateTime('created');
            $table->dateTime('modified');
            $table->string('status')->default('Active');
        });

        $admin = new User();
        $admin->forceFill([
            'id' => 990013,
            'user_type' => 'Admin',
            'name' => 'Status Test Admin',
            'email' => 'status-admin@example.test',
            'status' => 'Active',
        ]);
        $admin->exists = true;

        $this->actingAs($admin, 'admin');
    }

    public function test_valid_legacy_status_save_request_is_canonicalized(): void
    {
        $this->post(route('admin.unit-types.store'), [
            'unit_type_name' => 'Meter',
            'status' => '1',
        ])->assertRedirect(route('admin.unit-types.index'));

        $this->assertSame('Active', DB::table('unit_type')->value('status'));
    }

    public function test_invalid_status_save_request_is_rejected_without_writing(): void
    {
        $this->from(route('admin.unit-types.create'))->post(route('admin.unit-types.store'), [
            'unit_type_name' => 'Meter',
            'status' => 'Pending',
        ])->assertRedirect(route('admin.unit-types.create'))
            ->assertSessionHas('message', 'The status field must be one of: Active, Inactive, 1, or 0.');

        $this->assertSame(0, DB::table('unit_type')->count());
    }

    public function test_status_dropdown_uses_only_canonical_editable_values(): void
    {
        $this->get(route('admin.unit-types.create'))
            ->assertOk()
            ->assertSee('value="Active"', escape: false)
            ->assertSee('value="Inactive"', escape: false)
            ->assertDontSee('value="Deleted"', escape: false);
    }

    public function test_model_scopes_and_transition_guard_work_on_disposable_sqlite(): void
    {
        DB::table('unit_type')->insert([
            ['unit_type_name' => 'Active unit', 'created' => now(), 'modified' => now(), 'status' => 'Active'],
            ['unit_type_name' => 'Inactive unit', 'created' => now(), 'modified' => now(), 'status' => 'Inactive'],
            ['unit_type_name' => 'Deleted unit', 'created' => now(), 'modified' => now(), 'status' => 'Deleted'],
        ]);

        $this->assertSame(['Active unit'], UnitType::active()->pluck('unit_type_name')->all());
        $this->assertSame(['Inactive unit'], UnitType::inactive()->pluck('unit_type_name')->all());
        $this->assertNotContains('Deleted unit', UnitType::notDeleted()->pluck('unit_type_name')->all());

        $unit = UnitType::where('unit_type_name', 'Active unit')->firstOrFail();
        $unit->status = 'Inactive';
        $unit->save();
        $unit->status = 'Deleted';
        $unit->save();

        $this->expectException(InvalidRecordStatusTransition::class);
        $unit->status = 'Active';
        $unit->save();
    }

    public function test_selected_master_crud_routes_still_resolve(): void
    {
        foreach ([
            'departments', 'process-items', 'machines', 'items', 'colours',
            'warehouses', 'ware-house-compartments', 'unit-types', 'item-types', 'cotings',
        ] as $resource) {
            $this->assertTrue(Route::has("admin.{$resource}.index"));
            $this->assertTrue(Route::has("admin.{$resource}.store"));
            $this->assertTrue(Route::has("admin.{$resource}.update"));
        }
    }
}
