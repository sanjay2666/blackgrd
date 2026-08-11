<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\Individual;
use App\Services\VendorMasterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function index(Request $request): View
    {
        $q = Individual::where('type', 'vendors')->where('status', '!=', 'Deleted')->with('activeAddresses')->when($request->filled('search'), fn ($x) => $x->where(fn ($s) => $s->where('name', 'like', '%'.$request->string('search').'%')->orWhere('vendor_code', 'like', '%'.$request->string('search').'%')->orWhere('gstin', 'like', '%'.$request->string('search').'%')->orWhere('phone', 'like', '%'.$request->string('search').'%')))->when(in_array($request->status, ['Active', 'Inactive'], true), fn ($x) => $x->where('status', $request->status));

        return view('admin.vendors.index', ['vendors' => $q->latest('id')->paginate(config('app.pagination_limit'))->withQueryString()]);
    }

    public function create(): View
    {
        return view('admin.vendors.create');
    }

    public function store(Request $r, VendorMasterService $s): RedirectResponse
    {
        $s->save(new Individual(), $this->validated($r), $r);

        return redirect()->route('admin.vendors.index')->with('message', 'Vendor added successfully.')->with('messageClass', 'successClass');
    }

    public function edit(Individual $vendor): View
    {
        $this->assertVendor($vendor);

        return view('admin.vendors.edit', compact('vendor'));
    }

    public function update(Request $r, Individual $vendor, VendorMasterService $s): RedirectResponse
    {
        $this->assertVendor($vendor);
        $s->save($vendor, $this->validated($r), $r);

        return redirect()->route('admin.vendors.edit', $vendor)->with('message', 'Vendor updated successfully.')->with('messageClass', 'successClass');
    }

    public function activate(Individual $vendor, VendorMasterService $s): RedirectResponse
    {
        $this->assertVendor($vendor);
        $s->transition($vendor, 'Active');

        return back()->with('message', 'Vendor activated successfully.');
    }

    public function deactivate(Individual $vendor, VendorMasterService $s): RedirectResponse
    {
        $this->assertVendor($vendor);
        $s->transition($vendor, 'Inactive');

        return back()->with('message', 'Vendor deactivated successfully.');
    }

    public function destroy(Request $r, Individual $vendor, VendorMasterService $s): RedirectResponse
    {
        $this->assertVendor($vendor);
        $s->remove($vendor, $r);

        return back()->with('message', 'Vendor removed successfully.');
    }

    private function validated(Request $r): array
    {
        return $r->validate(['name' => 'required|string|max:255', 'vendor_code' => 'nullable|string|max:50', 'company_name' => 'nullable|string|max:100', 'gstin' => 'nullable|string|max:20', 'pan' => 'nullable|string|max:20', 'phone' => 'nullable|string|max:25', 'whatsapp' => 'nullable|string|max:25', 'email' => 'nullable|email|max:100', 'status' => ['required', 'in:Active,Inactive']]);
    }

    private function assertVendor(Individual $v): void
    {
        abort_if($v->type !== 'vendors' || $v->status === RecordStatus::Deleted->value, 404);
    }
}
