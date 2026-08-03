<?php

namespace Tests\Unit\Status;

use App\Casts\RecordStatusCast;
use App\Models\Company;
use App\Models\Concerns\HasRecordStatus;
use App\Models\Item;
use App\Rules\RecordStatusRule;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Tests\TestCase;

class RecordStatusModelTest extends TestCase
{
    public function test_selected_master_model_reads_legacy_assignments_canonically(): void
    {
        $company = new Company(['status' => 1]);
        $item = new Item(['status' => '0']);

        $this->assertSame('Active', $company->status);
        $this->assertSame('Active', $company->getAttributes()['status']);
        $this->assertSame('Inactive', $item->status);
        $this->assertSame('Inactive', $item->getAttributes()['status']);
        $this->assertSame(RecordStatusCast::class, $company->getCasts()['status']);
        $this->assertContains(HasRecordStatus::class, class_uses_recursive($company));
    }

    public function test_invalid_assignment_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Company(['status' => 'Pending']);
    }

    public function test_shared_scopes_build_the_expected_status_constraints(): void
    {
        $this->assertSame(['Active'], Company::active()->getBindings());
        $this->assertSame(['Inactive'], Company::inactive()->getBindings());
        $this->assertSame(['Deleted'], Company::notDeleted()->getBindings());
    }

    public function test_validation_accepts_canonical_and_legacy_values(): void
    {
        foreach (['Active', 'inactive', 1, 0, '1', '0'] as $status) {
            $validator = Validator::make(['status' => $status], [
                'status' => ['required', new RecordStatusRule],
            ]);

            $this->assertFalse($validator->fails(), "Status [{$status}] should be accepted.");
        }
    }

    public function test_validation_rejects_deleted_and_business_values_for_forms(): void
    {
        foreach (['Deleted', 'Pending', null] as $status) {
            $validator = Validator::make(['status' => $status], [
                'status' => ['required', new RecordStatusRule],
            ]);

            $this->assertTrue($validator->fails());
        }

        $validator = Validator::make(['status' => 'Pending'], [
            'status' => ['required', new RecordStatusRule],
        ]);
        $this->assertSame(
            'The status field must be one of: Active, Inactive, 1, or 0.',
            $validator->errors()->first('status'),
        );
    }
}
