<?php

namespace Tests\Unit\Status;

use App\Enums\AccountStatus;
use App\Enums\MachineOperationalState;
use App\Enums\RecordStatus;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RecordStatusTest extends TestCase
{
    public function test_status_categories_have_separate_canonical_values(): void
    {
        $this->assertSame(['Active', 'Inactive', 'Deleted'], RecordStatus::values());
        $this->assertSame(['Active', 'Inactive', 'Locked', 'Disabled'], AccountStatus::values());
        $this->assertSame(
            ['Available', 'Running', 'Idle', 'Maintenance', 'Breakdown', 'Blocked'],
            MachineOperationalState::values(),
        );
    }

    public function test_record_labels_and_options_are_canonical(): void
    {
        $this->assertSame('Active', RecordStatus::Active->label());
        $this->assertSame([
            'Active' => 'Active',
            'Inactive' => 'Inactive',
            'Deleted' => 'Deleted',
        ], RecordStatus::options());
        $this->assertSame([
            'Active' => 'Active',
            'Inactive' => 'Inactive',
        ], RecordStatus::formOptions());
        $this->assertTrue(RecordStatus::Active->isActive());
        $this->assertTrue(RecordStatus::Deleted->isDeleted());
    }

    public function test_legacy_numeric_and_string_values_are_normalized(): void
    {
        $expectations = [
            [1, RecordStatus::Active],
            [0, RecordStatus::Inactive],
            ['1', RecordStatus::Active],
            ['0', RecordStatus::Inactive],
            ['Active', RecordStatus::Active],
            [' inactive ', RecordStatus::Inactive],
            ['DELETED', RecordStatus::Deleted],
        ];

        foreach ($expectations as [$value, $expected]) {
            $this->assertSame($expected, RecordStatus::fromLegacyValue($value));
        }
    }

    public function test_null_and_business_states_are_not_record_statuses(): void
    {
        $this->assertNull(RecordStatus::tryFromLegacyValue(null));
        $this->assertNull(RecordStatus::tryFromLegacyValue('Pending'));
        $this->assertNull(RecordStatus::tryFromLegacyValue('Complete'));
        $this->assertNull(RecordStatus::tryFromLegacyValue('Accepted'));
    }

    public function test_invalid_value_throws_instead_of_falling_back(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid record status value.');

        RecordStatus::fromLegacyValue('Pending');
    }
}
