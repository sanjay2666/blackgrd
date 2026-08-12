<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SaleOrderItem;
use App\Models\WorkflowVersion;
use App\Services\WorkflowAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkflowAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $saleOrderItems = SaleOrderItem::query()
            ->where('status', 'Active')
            ->where('is_deleted', false)
            ->with(['saleOrder', 'workflowVersion.definition'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.$request->string('search').'%';
                $query->where(function ($nested) use ($search): void {
                    $nested->where('item_name', 'like', $search)
                        ->orWhere('grey_quality', 'like', $search)
                        ->orWhereHas('saleOrder', fn ($order) => $order->where('sale_order_number', 'like', $search));
                });
            })
            ->orderByDesc('id')
            ->paginate(config('app.pagination_limit'))
            ->withQueryString();

        $workflowVersions = WorkflowVersion::query()
            ->assignable()
            ->with('definition')
            ->get()
            ->sortBy([
                ['definition.workflow_name', 'asc'],
                ['version_number', 'desc'],
            ])
            ->values();

        return view('admin.workflow_assignments.index', compact('saleOrderItems', 'workflowVersions'));
    }

    public function update(
        Request $request,
        SaleOrderItem $sale_order_item,
        WorkflowAssignmentService $service,
    ): RedirectResponse {
        $attributes = $request->validate([
            'workflow_version_id' => 'nullable|integer',
        ]);
        $service->assign(
            $sale_order_item,
            $attributes['workflow_version_id'] ?? null,
            auth('admin')->id(),
            $request,
        );

        return back()
            ->with('message', 'Sale Order Item Workflow updated successfully.')
            ->with('messageClass', 'successClass');
    }
}
