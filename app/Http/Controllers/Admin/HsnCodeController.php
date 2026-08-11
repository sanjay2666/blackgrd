<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\GstRate;
use App\Models\HsnCode;
use App\Rules\RecordStatusRule;
use App\Services\GstHsnMasterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class HsnCodeController extends Controller
{
    public function __construct(private readonly GstHsnMasterService $masters)
    {
    }

    public function index(Request $request)
    {
        $query = HsnCode::with('gstRate')->notDeleted();
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(fn ($q) => $q->where('hsn_code', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%"));
        }
        if (in_array($request->input('status'), ['Active', 'Inactive'], true)) {
            $query->where('status', $request->input('status'));
        }
        $hsnCodes = $query->orderBy('hsn_code')->paginate(config('app.pagination_limit'))->withQueryString();

        return view('admin.hsn_codes.index', compact('hsnCodes'));
    }

    public function create()
    {
        return view('admin.hsn_codes.create', ['gstRates' => GstRate::active()->orderBy('gst_rate')->get()]);
    }

    public function store(Request $request)
    {
        $v = $this->validator($request);
        if ($v->fails()) {
            return $this->failure($v->errors()->first());
        } $this->masters->createHsn($request->only(['hsn_code', 'description', 'gst_rate_id', 'status']), $request);

        return $this->success('HSN Code added successfully.');
    }

    public function edit($id)
    {
        $hsnCode = $this->find($id);
        $gstRates = GstRate::active()->orderBy('gst_rate')->get();

        return view('admin.hsn_codes.edit', compact('hsnCode', 'gstRates'));
    }

    public function update(Request $request, $id)
    {
        $hsn = $this->find($id);
        $v = $this->validator($request, $hsn);
        if ($v->fails()) {
            return $this->failure($v->errors()->first());
        } $this->masters->updateHsn($hsn, $request->only(['hsn_code', 'description', 'gst_rate_id', 'status']), $request);

        return $this->success('HSN Code updated successfully.');
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
        $hsn = $this->find($id);
        try {
            $this->masters->assertCanDelete($hsn);
            $this->masters->setStatus($hsn, RecordStatus::Deleted, $request);

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    private function validator(Request $request, ?HsnCode $hsn = null)
    {
        $v = Validator::make($request->all(), ['hsn_code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9][A-Za-z0-9 .\/-]*$/'], 'description' => 'nullable|string|max:1000', 'gst_rate_id' => 'nullable|integer|exists:gst_rates,gst_rate_id', 'status' => ['required', new RecordStatusRule()]]);
        $v->after(function ($v) use ($request, $hsn): void {
            $code = strtoupper(GstHsnMasterService::normalizeHsn($request->hsn_code));
            $q = HsnCode::whereRaw('UPPER(hsn_code) = ?', [$code])->where('status', '!=', 'Deleted');
            if ($hsn) {
                $q->where('hsn_code_id', '!=', $hsn->hsn_code_id);
            } if ($q->exists()) {
                $v->errors()->add('hsn_code', 'This HSN Code already exists.');
            }
        });

        return $v;
    }

    private function find($id): HsnCode
    {
        $hsn = HsnCode::whereKey(dec($id))->firstOrFail();
        abort_if($hsn->status === RecordStatus::Deleted->value, 404);

        return $hsn;
    }

    private function changeStatus(Request $r, $id, RecordStatus $status)
    {
        $this->masters->setStatus($this->find($id), $status, $r);

        return $this->success('HSN Code status updated successfully.');
    }

    private function success(string $message)
    {
        Session::put('message', $message);
        Session::put('messageClass', 'successClass');

        return redirect()->route('admin.hsn-codes.index');
    }

    private function failure(string $message)
    {
        Session::put('message', $message);
        Session::put('messageClass', 'errorClass');

        return redirect()->back()->withInput();
    }
}
