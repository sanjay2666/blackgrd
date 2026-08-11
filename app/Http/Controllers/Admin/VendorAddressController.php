<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Individual;
use App\Models\IndividualAddress;
use App\Services\VendorMasterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VendorAddressController extends Controller
{
    public function create(Individual $vendor): View
    {
        $this->assert($vendor);

        return view('admin.vendors.address-form', ['vendor' => $vendor, 'states' => DB::table('states')->where('status', 'Active')->orderBy('name')->get()]);
    }

    public function store(Request $r, Individual $vendor, VendorMasterService $s): RedirectResponse
    {
        $this->assert($vendor);
        $s->saveAddress($vendor, null, $this->validated($r), $r);

        return redirect()->route('admin.vendors.edit', $vendor)->with('message', 'Vendor address added successfully.')->with('messageClass', 'successClass');
    }

    public function edit(Individual $vendor, IndividualAddress $address): View
    {
        $this->assert($vendor);
        abort_unless((int) $address->individual_id === (int) $vendor->id, 404);

        return view('admin.vendors.address-form', ['vendor' => $vendor, 'address' => $address, 'states' => DB::table('states')->where('status', 'Active')->orderBy('name')->get()]);
    }

    public function update(Request $r, Individual $vendor, IndividualAddress $address, VendorMasterService $s): RedirectResponse
    {
        $this->assert($vendor);
        $s->saveAddress($vendor, $address, $this->validated($r), $r);

        return redirect()->route('admin.vendors.edit', $vendor)->with('message', 'Vendor address updated successfully.')->with('messageClass', 'successClass');
    }

    public function destroy(Request $r, Individual $vendor, IndividualAddress $address, VendorMasterService $s): RedirectResponse
    {
        $this->assert($vendor);
        $s->removeAddress($vendor, $address, $r);

        return back()->with('message', 'Vendor address removed successfully.');
    }

    private function validated(Request $r): array
    {
        return $r->validate(['address_type' => 'required|in:b,s', 'address_1' => 'required|string|max:5555', 'address_2' => 'required|string|max:5555', 'state_id' => 'required|integer', 'city' => 'required|string|max:255', 'zip_code' => 'required|string|max:10', 'default_address' => 'nullable|boolean']);
    }

    private function assert(Individual $v): void
    {
        abort_if($v->type !== 'vendors' || $v->status === 'Deleted', 404);
    }
}
