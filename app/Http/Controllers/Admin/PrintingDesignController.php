<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\PrintingDesign;
use App\Rules\RecordStatusRule;
use App\Services\PrintingDesignMasterService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PrintingDesignController extends Controller
{
    public function index(Request $request): View
    {
        $query = PrintingDesign::query()->notDeleted();
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(fn ($q) => $q->where('design_name', 'like', '%'.$search.'%')->orWhere('design_code', 'like', '%'.$search.'%'));
        }
        if ($request->filled('status') && in_array($request->input('status'), ['Active', 'Inactive'], true)) {
            $query->where('status', $request->input('status'));
        }

        return view('admin.printing_designs.index', [
            'designs' => $query->orderByRaw('COALESCE(display_order, 999999), design_name, id')->paginate(config('app.pagination_limit'))->withQueryString(),
            'statusOptions' => RecordStatus::formOptions(),
        ]);
    }

    public function create(): View
    {
        return view('admin.printing_designs.form', ['design' => new PrintingDesign(), 'statusOptions' => RecordStatus::formOptions()]);
    }

    public function store(Request $request, PrintingDesignMasterService $service)
    {
        $service->save(new PrintingDesign(), $this->validated($request));

        return redirect()->route('admin.printing-designs.index')->with('message', 'Printing Design added successfully.')->with('messageClass', 'successClass');
    }

    public function edit($id): View
    {
        $design = PrintingDesign::whereKey(dec($id))->notDeleted()->firstOrFail();

        return view('admin.printing_designs.form', ['design' => $design, 'statusOptions' => RecordStatus::formOptions()]);
    }

    public function update(Request $request, $id, PrintingDesignMasterService $service)
    {
        $design = PrintingDesign::whereKey(dec($id))->notDeleted()->firstOrFail();
        $service->save($design, $this->validated($request));

        return redirect()->route('admin.printing-designs.index')->with('message', 'Printing Design updated successfully.')->with('messageClass', 'successClass');
    }

    public function destroy($id, PrintingDesignMasterService $service)
    {
        $service->rejectDeletion(PrintingDesign::whereKey(dec($id))->notDeleted()->firstOrFail());
    }

    public function activate($id, PrintingDesignMasterService $service)
    {
        $service->transition(PrintingDesign::whereKey(dec($id))->notDeleted()->firstOrFail(), 'Active');

        return back();
    }

    public function deactivate($id, PrintingDesignMasterService $service)
    {
        $service->transition(PrintingDesign::whereKey(dec($id))->notDeleted()->firstOrFail(), 'Inactive');

        return back();
    }

    public function options(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $query = PrintingDesign::query()->active();
        if ($search !== '') {
            $query->where(fn ($q) => $q->where('design_name', 'like', '%'.$search.'%')->orWhere('design_code', 'like', '%'.$search.'%'));
        }
        $designs = $query->orderBy('design_name')->limit(50)->get(['id', 'design_name', 'design_code']);

        return response()->json(['results' => $designs->map(fn (PrintingDesign $design): array => ['id' => $design->getKey(), 'text' => $design->design_name, 'code' => $design->design_code])->values()]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'design_name' => 'required|string|max:255',
            'design_code' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/'],
            'description' => 'nullable|string|max:5000',
            'display_order' => 'nullable|integer|min:0',
            'status' => ['required', new RecordStatusRule()],
        ]);
    }
}
