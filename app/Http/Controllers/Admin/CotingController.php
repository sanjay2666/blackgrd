<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\Coting;
use App\Rules\RecordStatusRule;
use App\Services\CotingMasterService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CotingController extends Controller
{
    public function index(Request $request): View
    {
        $query = Coting::query()->notDeleted();
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%'));
        }
        if ($request->filled('status') && in_array($request->input('status'), ['Active', 'Inactive'], true)) {
            $query->where('status', $request->input('status'));
        }
        return view('admin.cotings.index', ['cotings' => $query->orderByRaw('COALESCE(display_order, 999999), name, id')->paginate(config('app.pagination_limit'))->withQueryString(), 'statusOptions' => RecordStatus::formOptions()]);
    }

    public function create(): View { return view('admin.cotings.form', ['coting' => new Coting(), 'statusOptions' => RecordStatus::formOptions()]); }

    public function store(Request $request, CotingMasterService $service)
    {
        $service->save(new Coting(), $this->validated($request));
        return redirect()->route('admin.cotings.index')->with('message', 'Coating Type added successfully.')->with('messageClass', 'successClass');
    }

    public function edit($id): View
    {
        return view('admin.cotings.form', ['coting' => Coting::whereKey(dec($id))->notDeleted()->firstOrFail(), 'statusOptions' => RecordStatus::formOptions()]);
    }

    public function update(Request $request, $id, CotingMasterService $service)
    {
        $service->save(Coting::whereKey(dec($id))->notDeleted()->firstOrFail(), $this->validated($request));
        return redirect()->route('admin.cotings.index')->with('message', 'Coating Type updated successfully.')->with('messageClass', 'successClass');
    }

    public function destroy($id, CotingMasterService $service): never { $service->rejectDeletion(Coting::whereKey(dec($id))->notDeleted()->firstOrFail()); }
    public function activate($id, CotingMasterService $service) { $service->transition(Coting::whereKey(dec($id))->notDeleted()->firstOrFail(), 'Active'); return back(); }
    public function deactivate($id, CotingMasterService $service) { $service->transition(Coting::whereKey(dec($id))->notDeleted()->firstOrFail(), 'Inactive'); return back(); }

    public function options(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $query = Coting::query()->active()->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%')));
        return response()->json(['results' => $query->orderBy('name')->limit(50)->get(['id', 'name', 'code'])->map(fn (Coting $coting) => ['id' => $coting->id, 'text' => $coting->name, 'code' => $coting->code])->values()]);
    }

    private function validated(Request $request): array
    {
        return $request->validate(['name' => 'required|string|max:255', 'code' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/'], 'description' => 'nullable|string|max:5000', 'display_order' => 'nullable|integer|min:0', 'status' => ['required', new RecordStatusRule()]]);
    }
}
