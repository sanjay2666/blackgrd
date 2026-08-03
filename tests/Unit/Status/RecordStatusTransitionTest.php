<?php

namespace Tests\Unit\Status;

use App\Enums\RecordStatus;
use App\Exceptions\InvalidRecordStatusTransition;
use App\Support\RecordStatusTransition;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RecordStatusTransitionTest extends TestCase
{
    /** @return list<array{RecordStatus, RecordStatus}> */
    public static function allowedTransitions(): array
    {
        return [
            [RecordStatus::Active, RecordStatus::Inactive],
            [RecordStatus::Inactive, RecordStatus::Active],
            [RecordStatus::Active, RecordStatus::Deleted],
            [RecordStatus::Inactive, RecordStatus::Deleted],
            [RecordStatus::Active, RecordStatus::Active],
        ];
    }

    #[DataProvider('allowedTransitions')]
    public function test_allowed_transitions(RecordStatus $from, RecordStatus $to): void
    {
        RecordStatusTransition::ensureAllowed($from, $to);

        $this->addToAssertionCount(1);
    }

    public function test_deleted_record_cannot_be_activated_without_restore(): void
    {
        $this->expectException(InvalidRecordStatusTransition::class);
        $this->expectExceptionMessage('Deleted to Active');

        RecordStatusTransition::ensureAllowed(RecordStatus::Deleted, RecordStatus::Active);
    }

    public function test_explicit_future_restore_path_is_available(): void
    {
        RecordStatusTransition::ensureAllowed(
            RecordStatus::Deleted,
            RecordStatus::Active,
            explicitRestore: true,
        );

        $this->addToAssertionCount(1);
    }

    public function test_invalid_state_is_blocked_before_transition_check(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RecordStatusTransition::ensureAllowed('Pending', 'Active');
    }
}
