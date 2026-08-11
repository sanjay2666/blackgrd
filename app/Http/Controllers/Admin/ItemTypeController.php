<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\ItemType;
use App\Models\UnitType;
use App\Services\AuditLogger;
use App\Services\ItemTypeMasterService;
use App\Rules\RecordStatusRule;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use LogicException;

class ItemTypeController extends Controller
{
    public function __construct(private readonly ItemTypeMasterService $master, private readonly AuditLogger $audit)
    {
    }

    public function index(Request $request)
    {
        $query = ItemType::with('unitType');
        $query->notDeleted();

        if ($request->filled('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('item_type_name', 'like', '%'.$request->search.'%');
                $query->orWhere('short_code', 'like', '%'.$request->search.'%');
            });
        }
        if ($request->filled('status') && in_array($request->status, ['Active', 'Inactive'], true)) {
            $query->where('status', $request->status);
        }

        $itemTypes = $query->orderBy('item_type_id', 'desc')->paginate(config('app.pagination_limit'))->withQueryString();

        return view('admin.item_types.index', compact('itemTypes'));
    }

    public function create()
    {
        $unitTypes = UnitType::active()->orderBy('unit_type_id', 'asc')->get();

        return view('admin.item_types.create', compact('unitTypes'))->with('statusOptions', RecordStatus::formOptions());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_type_name' => ['required', 'string', 'max:100'],
            'short_code' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9_-]+$/'],
            'unit_type_id' => 'nullable',
            'is_purchase' => 'required',
            'is_work' => 'required',
            'is_department' => 'required',
            'status' => ['required', new RecordStatusRule],
        ], [
            'item_type_name.required' => 'Please enter Item Type Name.',
            'is_purchase.required' => 'Please enter Is Purchase.',
            'is_work.required' => 'Please enter Is Work.',
            'is_department.required' => 'Please enter Is Department.',
            'status.required' => 'Please select Status.',
        ]);
        $validator->after(fn ($validator) => $this->validateUnique($validator, $request));

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $itemType = new ItemType;
            $itemType->item_type_name = $request->item_type_name;
            $itemType->short_code = strtoupper(trim($request->short_code));
            $itemType->unit_type_id = $request->unit_type_id;
            $itemType->is_purchase = $request->is_purchase;
            $itemType->is_work = $request->is_work;
            $itemType->is_department = $request->is_department;
            $itemType->status = $request->status;
            $itemType->created = now();
            $itemType->modified = now();
            $itemType->save();

            DB::commit();
            $this->audit->recordAfterCommit(['module' => 'masters', 'action' => 'create', 'event' => 'item_type_created', 'description' => 'Item Type created.', 'auditable_type' => $itemType->getMorphClass(), 'auditable_id' => $itemType->getKey(), 'new_values' => $this->values($itemType), 'request' => $request]);
            Session::put('message', 'Item Types added successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.item-types.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to save Item Types. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function edit($id)
    {
        $id = dec($id);
        $itemType = ItemType::where('item_type_id', $id)->firstOrFail();
        if ($itemType->status === 'Deleted') {
            abort(404);
        }

        $unitTypes = UnitType::active()->orderBy('unit_type_id', 'asc')->get();

        return view('admin.item_types.edit', compact('itemType', 'unitTypes'))->with('statusOptions', RecordStatus::formOptions());
    }

    public function update(Request $request, $id)
    {
        $id = dec($id);
        $itemType = ItemType::where('item_type_id', $id)->firstOrFail();
        if ($itemType->status === 'Deleted') {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'item_type_name' => ['required', 'string', 'max:100'],
            'short_code' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9_-]+$/'],
            'unit_type_id' => 'nullable',
            'is_purchase' => 'required',
            'is_work' => 'required',
            'is_department' => 'required',
            'status' => ['required', new RecordStatusRule],
        ], [
            'item_type_name.required' => 'Please enter Item Type Name.',
            'is_purchase.required' => 'Please enter Is Purchase.',
            'is_work.required' => 'Please enter Is Work.',
            'is_department.required' => 'Please enter Is Department.',
            'status.required' => 'Please select Status.',
        ]);
        $validator->after(fn ($validator) => $this->validateUnique($validator, $request, $itemType));

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        $before = $this->values($itemType);
        try {
            $this->master->assertMutable($itemType, ['item_type_name' => trim($request->item_type_name), 'short_code' => strtoupper(trim($request->short_code)), 'status' => $request->status]);
        } catch (LogicException $e) {
            return back()->withInput()->withErrors(['item_type_name' => $e->getMessage()]);
        }
        DB::beginTransaction();
        try {
            $itemType->item_type_name = $request->item_type_name;
            $itemType->short_code = strtoupper(trim($request->short_code));
            $itemType->unit_type_id = $request->unit_type_id;
            $itemType->is_purchase = $request->is_purchase;
            $itemType->is_work = $request->is_work;
            $itemType->is_department = $request->is_department;
            $itemType->status = $request->status;
            $itemType->modified = now();
            $itemType->save();

            DB::commit();
            $this->audit->recordAfterCommit(['module' => 'masters', 'action' => 'update', 'event' => 'item_type_updated', 'description' => 'Item Type updated.', 'auditable_type' => $itemType->getMorphClass(), 'auditable_id' => $itemType->getKey(), 'old_values' => $before, 'new_values' => $this->values($itemType), 'request' => $request]);
            Session::put('message', 'Item Types updated successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.item-types.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to update Item Types. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        $id = dec($id);
        $itemType = ItemType::where('item_type_id', $id)->firstOrFail();
        if ($itemType->status === 'Deleted') {
            abort(404);
        }

        if ($this->master->isReferenced($itemType)) {
            return response()->json(['success' => false, 'message' => 'Referenced Item Types cannot be deleted; deactivate them only when safe.'], 422);
        }
        try {
            $this->master->assertMutable($itemType, ['status' => 'Inactive']);
        } catch (LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        DB::beginTransaction();
        try {
            $itemType->status = 'Deleted';
            $itemType->modified = now();
            $itemType->save();

            DB::commit();
            Session::put('message', 'Item Types deactivated successfully.');
            Session::put('messageClass', 'successClass');

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to delete Item Types. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function activate(Request $request, $id)
    {
        return $this->changeStatus($request, $id, 'Active');
    }

    public function deactivate(Request $request, $id)
    {
        return $this->changeStatus($request, $id, 'Inactive');
    }

    private function changeStatus(Request $request, $id, string $status)
    {
        $itemType = ItemType::whereKey(dec($id))->where('status', '!=', 'Deleted')->firstOrFail();
        try {
            $this->master->assertMutable($itemType, ['status' => $status]);
        } catch (LogicException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }
        $before = $this->values($itemType);
        $itemType->status = $status;
        $itemType->modified = now();
        $itemType->save();
        $this->audit->recordAfterCommit(['module' => 'masters', 'action' => 'update', 'event' => 'item_type_status_changed', 'description' => 'Item Type status changed.', 'auditable_type' => $itemType->getMorphClass(), 'auditable_id' => $itemType->getKey(), 'old_values' => $before, 'new_values' => $this->values($itemType), 'request' => $request]);

        return redirect()->route('admin.item-types.index');
    }

    private function validateUnique($validator, Request $request, ?ItemType $itemType = null): void
    {
        $name = trim((string) $request->item_type_name);
        $code = strtoupper(trim((string) $request->short_code));
        $query = ItemType::where('status', '!=', 'Deleted');
        if ($itemType) $query->where($itemType->getKeyName(), '!=', $itemType->getKey());
        if ($query->whereRaw('LOWER(TRIM(item_type_name)) = ?', [strtolower($name)])->exists()) $validator->errors()->add('item_type_name', 'This Item Type Name already exists.');
        if (ItemType::where('status', '!=', 'Deleted')->when($itemType, fn ($q) => $q->where($itemType->getKeyName(), '!=', $itemType->getKey()))->where('short_code', $code)->exists()) $validator->errors()->add('short_code', 'This Item Type Code already exists.');
    }

    private function values(ItemType $itemType): array
    {
        return $itemType->only(['item_type_id', 'item_type_name', 'short_code', 'display_order', 'status', 'is_purchase', 'is_work', 'is_department']);
    }
}
