<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\Factory;
use App\Models\Shift;
use App\Rules\RecordStatusRule;
use App\Services\ShiftMasterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function index(Request $request): View
    {
        $query = Shift::query()->with('factory')->notDeleted()
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($s) => $s->where('shift_name', 'like', '%'.$request->string('search').'%')->orWhere('shift_code', 'like', '%'.$request->string('search').'%')))
            ->when($request->filled('factory_id'), fn ($q) => $q->where('factory_id', $request->integer('factory_id')))
            ->when(in_array($request->status, ['Active', 'Inactive'], true), fn ($q) => $q->where('status', $request->status));

        return view('admin.shifts.index', ['shifts' => $query->orderBy('display_order')->orderBy('start_time')->paginate(config('app.pagination_limit'))->withQueryString(), 'factories' => Factory::active()->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('admin.shifts.create', ['factories' => Factory::active()->orderBy('name')->get()]);
    }

    public function store(Request $request, ShiftMasterService $service): RedirectResponse
    {
        $service->save(new Shift(), $this->validated($request), $request);

        return redirect()->route('admin.shifts.index')->with('message', 'Shift added successfully.')->with('messageClass', 'successClass');
    }

    public function edit(Shift $shift): View
    {
        abort_if($shift->status === RecordStatus::Deleted->value, 404);

        return view('admin.shifts.edit', ['shift' => $shift, 'factories' => Factory::active()->orderBy('name')->get()]);
    }

    public function update(Request $request, Shift $shift, ShiftMasterService $service): RedirectResponse
    {
        abort_if($shift->status === RecordStatus::Deleted->value, 404);
        $service->save($shift, $this->validated($request), $request);

        return redirect()->route('admin.shifts.index')->with('message', 'Shift updated successfully.')->with('messageClass', 'successClass');
    }

    public function activate(Shift $shift, ShiftMasterService $service): RedirectResponse
    {
        abort_if($shift->status === RecordStatus::Deleted->value, 404);
        $service->transition($shift, RecordStatus::Active->value);

        return back()->with('message', 'Shift activated successfully.');
    }

    public function deactivate(Shift $shift, ShiftMasterService $service): RedirectResponse
    {
        abort_if($shift->status === RecordStatus::Deleted->value, 404);
        $service->transition($shift, RecordStatus::Inactive->value);

        return back()->with('message', 'Shift deactivated successfully.');
    }

    public function destroy(Request $request, Shift $shift, ShiftMasterService $service): RedirectResponse
    {
        abort_if($shift->status === RecordStatus::Deleted->value, 404);
        $service->remove($shift, $request);

        return back()->with('message', 'Shift removed successfully.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate(['shift_name' => 'required|string|max:100', 'shift_code' => 'nullable|string|max:30', 'start_time' => 'required|date_format:H:i', 'end_time' => 'required|date_format:H:i', 'factory_id' => 'nullable|integer', 'description' => 'nullable|string', 'display_order' => 'nullable|integer|min:0', 'status' => ['required', new RecordStatusRule()]]);
    }
}
