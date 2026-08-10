<?php

namespace Tests\Unit\Status;

use App\Enums\GatePassStatus;
use App\Enums\InspectionResult;
use App\Enums\InspectionStatus;
use App\Enums\InventoryAllocationStatus;
use App\Enums\InventoryMovementStatus;
use App\Enums\InventoryReceiptStatus;
use App\Enums\JobWorkStatus;
use App\Enums\PurchaseOrderDocumentStatus;
use App\Enums\SaleOrderDocumentStatus;
use App\Enums\WorkOrderExecutionStatus;
use App\Enums\WorkRequirementStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OperationalStatusEnumTest extends TestCase
{
    public static function enumValues(): array
    {
        return [
            [SaleOrderDocumentStatus::class, ['draft', 'pending_approval', 'approved', 'in_production', 'partially_dispatched', 'completed', 'on_hold', 'rejected', 'cancelled']],
            [PurchaseOrderDocumentStatus::class, ['draft', 'pending_approval', 'approved', 'partially_received', 'received', 'closed', 'on_hold', 'cancelled']],
            [WorkOrderExecutionStatus::class, ['created', 'material_requested', 'material_allotted', 'ready', 'started', 'partially_completed', 'completed', 'inspection_pending', 'passed', 'rejected', 'rework', 'on_hold', 'cancelled']],
            [WorkRequirementStatus::class, ['created', 'sent_to_warehouse', 'pending', 'partially_allotted', 'allotted', 'accepted', 'denied', 'cancelled', 'closed']],
            [InspectionStatus::class, ['pending', 'completed', 'cancelled']],
            [InspectionResult::class, ['pending', 'passed', 'partially_passed', 'rejected', 'defective', 'rework', 'completed']],
            [InventoryMovementStatus::class, ['draft', 'posted', 'partially_posted', 'reversed', 'cancelled']],
            [InventoryAllocationStatus::class, ['unallocated', 'partially_allocated', 'allocated', 'released', 'cancelled']],
            [InventoryReceiptStatus::class, ['pending', 'partially_received', 'received', 'rejected', 'closed']],
            [JobWorkStatus::class, ['requirement_raised', 'vendor_selected', 'approved', 'dispatched', 'partially_received', 'received', 'inspection_pending', 'shortage_pending', 'rework', 'closed', 'cancelled']],
            [GatePassStatus::class, ['draft', 'issued', 'partially_received', 'received', 'cancelled', 'closed']],
        ];
    }

    #[DataProvider('enumValues')]
    public function test_canonical_values_labels_options_and_unknown_rejection(string $enum, array $expected): void
    {
        $this->assertSame($expected, array_column($enum::cases(), 'value'));
        $this->assertSame($expected, array_keys($enum::options()));
        $this->assertNotContains('', array_values($enum::options()));
        $this->assertNull($enum::tryFrom('unknown_status'));
        $this->assertNull($enum::tryFrom(''));
    }
}
