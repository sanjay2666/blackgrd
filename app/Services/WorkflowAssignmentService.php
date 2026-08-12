<?php

namespace App\Services;

use App\Models\SaleOrderItem;
use Illuminate\Http\Request;

final class WorkflowAssignmentService
{
    public function __construct(
        private readonly SaleOrderRequirementService $requirements,
        private readonly AuditLogger $audit,
    ) {
    }

    public function assign(
        SaleOrderItem $saleOrderItem,
        mixed $workflowVersionId,
        ?int $actorId,
        Request $request,
    ): void {
        $this->requirements->assertCanMutate($saleOrderItem);
        $reference = $this->requirements->workflowReference($workflowVersionId);
        $before = $saleOrderItem->only(['workflow_definition_id', 'workflow_version_id']);

        $saleOrderItem->fill([
            ...$reference,
            'modified_by' => $actorId,
            'modified_at' => now(),
        ])->save();

        $this->audit->recordAfterCommit([
            'module' => 'processes',
            'action' => 'update',
            'event' => 'sale_order_item_workflow_assigned',
            'description' => 'Published Workflow Version reference changed for a Sale Order Item.',
            'auditable_type' => $saleOrderItem->getMorphClass(),
            'auditable_id' => $saleOrderItem->id,
            'old_values' => $before,
            'new_values' => $saleOrderItem->only(['workflow_definition_id', 'workflow_version_id']),
            'request' => $request,
        ]);
    }
}
