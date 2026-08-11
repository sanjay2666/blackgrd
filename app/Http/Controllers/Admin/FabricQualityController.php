<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\FabricQuality;
use App\Rules\RecordStatusRule;
use App\Services\FabricQualityMasterService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class FabricQualityController extends Controller
{
    public function index(Request $request): View
    {
        $query = FabricQuality::query()->notDeleted();
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(fn ($q) => $q->where('quality_name', 'like', '%'.$search.'%')->orWhere('quality_code', 'like', '%'.$search.'%'));
        }
        foreach (['gsm', 'width'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, 'like', '%'.trim((string) $request->input($field)).'%');
            }
        }
        if ($request->filled('status') && in_array($request->input('status'), ['Active', 'Inactive'], true)) {
            $query->where('status', $request->input('status'));
        }

        return view('admin.fabric_qualities.index', [
            'qualities' => $query->orderByRaw('COALESCE(display_order, 999999), id')->paginate(config('app.pagination_limit'))->withQueryString(),
            'statusOptions' => RecordStatus::formOptions(),
        ]);
    }

    public function create(): View
    {
        return view('admin.fabric_qualities.form', ['quality' => new FabricQuality(), 'statusOptions' => RecordStatus::formOptions()]);
    }

    public function store(Request $request, FabricQualityMasterService $service)
    {
        $service->save(new FabricQuality(), $this->validated($request));

        return redirect()->route('admin.fabric-qualities.index')->with('message', 'Fabric Quality added successfully.')->with('messageClass', 'successClass');
    }

    public function edit($id): View
    {
        $quality = FabricQuality::whereKey(dec($id))->firstOrFail();
        abort_if($quality->status === 'Deleted', 404);

        return view('admin.fabric_qualities.form', ['quality' => $quality, 'statusOptions' => RecordStatus::formOptions()]);
    }

    public function update(Request $request, $id, FabricQualityMasterService $service)
    {
        $quality = FabricQuality::whereKey(dec($id))->firstOrFail();
        abort_if($quality->status === 'Deleted', 404);
        $service->save($quality, $this->validated($request));

        return redirect()->route('admin.fabric-qualities.index')->with('message', 'Fabric Quality updated successfully.')->with('messageClass', 'successClass');
    }

    public function destroy($id, FabricQualityMasterService $service)
    {
        $quality = FabricQuality::whereKey(dec($id))->firstOrFail();
        $service->rejectDeletion($quality);
    }

    public function activate($id, FabricQualityMasterService $service)
    {
        $quality = FabricQuality::whereKey(dec($id))->firstOrFail();
        $service->transition($quality, 'Active');

        return back();
    }

    public function deactivate($id, FabricQualityMasterService $service)
    {
        $quality = FabricQuality::whereKey(dec($id))->firstOrFail();
        $service->transition($quality, 'Inactive');

        return back();
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'quality_name' => 'required|string|max:255', 'quality_code' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/'],
            'description' => 'nullable|string|max:5000', 'gsm' => 'nullable|string|max:25', 'width' => 'nullable|string|max:22',
            'display_order' => 'nullable|integer|min:0', 'status' => ['required', new RecordStatusRule()],
        ]);
    }
}
