<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\UnitType;
use App\Rules\RecordStatusRule;
use App\Services\UnitTypeService;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UnitTypeController extends Controller
{
    public function __construct(private readonly UnitTypeService $units) {}

    public function index(Request $request)
    {
        $query = UnitType::query()->notDeleted();
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('unit_type_name', 'like', "%{$search}%");
                if (Schema::hasColumn('unit_type', 'unit_code')) {
                    $builder->orWhere('unit_code', 'like', "%{$search}%");
                }
            });
        }
        if ($request->filled('status') && in_array($request->input('status'), ['Active', 'Inactive'], true)) {
            $query->where('status', $request->input('status'));
        }

        $orderColumn = Schema::hasColumn('unit_type', 'display_order') ? 'display_order' : 'unit_type_id';
        $unitTypes = $query->orderBy($orderColumn)->orderBy('unit_type_id')->paginate(config('app.pagination_limit'))->withQueryString();

        return view('admin.unit_types.index', compact('unitTypes'));
    }

    public function create()
    {
        return view('admin.unit_types.create', ['statusOptions' => RecordStatus::formOptions()]);
    }

    public function store(Request $request)
    {
        $validator = $this->validator($request);
        if ($validator->fails()) {
            return $this->failure($validator->errors()->first());
        }

        try {
            $this->units->create($request->only(['unit_type_name', 'unit_code', 'description', 'decimal_places', 'display_order', 'status']), $request);
            return $this->success('Unit added successfully.');
        } catch (Exception $exception) {
            return $this->failure('Failed to save Unit. '.$exception->getMessage());
        }
    }

    public function edit($id)
    {
        $unitType = $this->find($id);
        return view('admin.unit_types.edit', compact('unitType'))->with('statusOptions', RecordStatus::formOptions());
    }

    public function update(Request $request, $id)
    {
        $unitType = $this->find($id);
        $validator = $this->validator($request, $unitType);
        if ($validator->fails()) {
            return $this->failure($validator->errors()->first());
        }

        try {
            $this->units->update($unitType, $request->only(['unit_type_name', 'unit_code', 'description', 'decimal_places', 'display_order', 'status']), $request);
            return $this->success('Unit updated successfully.');
        } catch (Exception $exception) {
            return $this->failure('Failed to update Unit. '.$exception->getMessage());
        }
    }

    public function activate(Request $request, $id)
    {
        return $this->changeStatus($request, $id, RecordStatus::Active);
    }

    public function deactivate(Request $request, $id)
    {
        return $this->changeStatus($request, $id, RecordStatus::Inactive);
    }

    public function destroy(Request $request, $id)
    {
        $unitType = $this->find($id);
        try {
            $this->units->assertCanDelete($unitType);
            $this->units->setStatus($unitType, RecordStatus::Deleted, $request);
            return response()->json(['success' => true]);
        } catch (Exception $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    private function changeStatus(Request $request, $id, RecordStatus $status)
    {
        try {
            $this->units->setStatus($this->find($id), $status, $request);
            return $this->success('Unit status updated successfully.');
        } catch (Exception $exception) {
            return $this->failure('Failed to update Unit status. '.$exception->getMessage());
        }
    }

    private function find($id): UnitType
    {
        $unit = UnitType::whereKey(dec($id))->firstOrFail();
        abort_if($unit->status === RecordStatus::Deleted->value, 404);
        return $unit;
    }

    private function validator(Request $request, ?UnitType $unit = null)
    {
        $rules = [
            'unit_type_name' => ['required', 'string', 'max:255', Rule::unique('unit_type', 'unit_type_name')->ignore($unit?->getKey(), 'unit_type_id')],
            'status' => ['required', new RecordStatusRule],
        ];
        if (Schema::hasColumn('unit_type', 'unit_code')) {
            $rules['unit_code'] = ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('unit_type', 'unit_code')->where(fn (Builder $query) => $query->whereNotNull('unit_code'))->ignore($unit?->getKey(), 'unit_type_id')];
            $rules['description'] = ['nullable', 'string', 'max:1000'];
            $rules['decimal_places'] = ['nullable', 'integer', 'between:0,6'];
            $rules['display_order'] = ['nullable', 'integer', 'min:0', 'max:4294967295'];
        }
        $validator = Validator::make($request->all(), $rules, ['unit_type_name.required' => 'Please enter Unit Name.']);
        $validator->after(function ($validator) use ($request, $unit): void {
            $name = mb_strtolower(trim((string) $request->input('unit_type_name')));
            $query = UnitType::query()->whereRaw('LOWER(TRIM(unit_type_name)) = ?', [$name]);
            if ($unit) {
                $query->where('unit_type_id', '!=', $unit->getKey());
            }
            if ($query->exists()) {
                $validator->errors()->add('unit_type_name', 'The Unit Name has already been taken.');
            }
            if (Schema::hasColumn('unit_type', 'unit_code') && filled($request->input('unit_code'))) {
                $code = mb_strtolower(trim((string) $request->input('unit_code')));
                $codeQuery = UnitType::query()->whereNotNull('unit_code')->whereRaw('LOWER(TRIM(unit_code)) = ?', [$code]);
                if ($unit) {
                    $codeQuery->where('unit_type_id', '!=', $unit->getKey());
                }
                if ($codeQuery->exists()) {
                    $validator->errors()->add('unit_code', 'The Short Code has already been taken.');
                }
            }
        });
        return $validator;
    }

    private function success(string $message)
    {
        Session::put('message', $message);
        Session::put('messageClass', 'successClass');
        return redirect()->route('admin.unit-types.index');
    }

    private function failure(string $message)
    {
        Session::put('message', $message);
        Session::put('messageClass', 'errorClass');
        return redirect()->back()->withInput();
    }
}
