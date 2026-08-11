<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\FabricFaultReason;
use App\Models\ProcessItem;
use App\Rules\RecordStatusRule;
use App\Services\FabricFaultReasonMasterService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class FabricFaultReasonController extends Controller
{
    public function index(Request $request): View
    {
        $query = FabricFaultReason::query()->where('status', '!=', 'Deleted')->with('process');
        if ($request->filled('search')) {
            $query->where('reason', 'like', '%'.trim((string) $request->input('search')).'%');
        }
        if ($request->filled('process_id') && ctype_digit((string) $request->input('process_id'))) {
            $query->where('process_id', (int) $request->input('process_id'));
        }
        if (in_array($request->input('status'), ['Active', 'Inactive'], true)) {
            $query->where('status', $request->input('status'));
        }
        return view('admin.fabric_fault_reasons.index', [
            'reasons' => $query->orderBy('process_id')->orderBy('reason')->paginate(config('app.pagination_limit'))->withQueryString(),
            'processes' => ProcessItem::query()->where('status', '!=', 'Deleted')->orderBy('process_name')->get(),
            'statusOptions' => RecordStatus::formOptions(),
        ]);
    }

    public function create(): View
    {
        return view('admin.fabric_fault_reasons.form', ['reason' => new FabricFaultReason(), 'processes' => $this->processes(), 'statusOptions' => RecordStatus::formOptions()]);
    }

    public function store(Request $request, FabricFaultReasonMasterService $service)
    {
        $service->save(new FabricFaultReason(), $this->validated($request));
        return redirect()->route('admin.fabric-fault-reasons.index')->with('message', 'Reason added successfully.')->with('messageClass', 'successClass');
    }

    public function edit($id): View
    {
        $reason = FabricFaultReason::whereKey(dec($id))->firstOrFail();
        abort_if($reason->status === 'Deleted', 404);
        return view('admin.fabric_fault_reasons.form', ['reason' => $reason, 'processes' => $this->processes(), 'statusOptions' => RecordStatus::formOptions()]);
    }

    public function update(Request $request, $id, FabricFaultReasonMasterService $service)
    {
        $reason = FabricFaultReason::whereKey(dec($id))->firstOrFail();
        abort_if($reason->status === 'Deleted', 404);
        $service->save($reason, $this->validated($request));
        return redirect()->route('admin.fabric-fault-reasons.index')->with('message', 'Reason updated successfully.')->with('messageClass', 'successClass');
    }

    public function destroy($id, FabricFaultReasonMasterService $service): never
    {
        $service->rejectDeletion(FabricFaultReason::whereKey(dec($id))->firstOrFail());
    }

    public function activate($id, FabricFaultReasonMasterService $service)
    {
        $service->transition(FabricFaultReason::whereKey(dec($id))->firstOrFail(), 'Active');
        return back();
    }

    public function deactivate($id, FabricFaultReasonMasterService $service)
    {
        $service->transition(FabricFaultReason::whereKey(dec($id))->firstOrFail(), 'Inactive');
        return back();
    }

    public function options(Request $request, FabricFaultReasonMasterService $service)
    {
        $processId = (int) $request->query('process_id');
        abort_unless(ProcessItem::query()->whereKey($processId)->where('status', 'Active')->exists(), 422, 'Invalid Process.');
        return response()->json(FabricFaultReason::query()->where('process_id', $processId)->where('status', 'Active')->orderBy('reason')->get(['id', 'reason']));
    }

    private function processes()
    {
        return ProcessItem::query()->where('status', 'Active')->orderBy('process_name')->get();
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate(['process_id' => 'required|integer', 'reason' => 'required|string|max:255', 'status' => ['required', new RecordStatusRule()]]);
    }
}
