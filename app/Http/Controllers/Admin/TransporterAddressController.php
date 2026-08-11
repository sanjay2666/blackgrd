<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Individual;
use App\Models\IndividualAddress;
use App\Services\TransporterMasterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TransporterAddressController extends Controller
{
    public function create(Individual $transporter): View
    {
        $this->assert($transporter);
        return view('admin.transporters.address-form', ['transporter' => $transporter,'states' => DB::table('states')->where('status', 'Active')->orderBy('name')->get()]);
    }
    public function store(Request $request, Individual $transporter, TransporterMasterService $service): RedirectResponse
    {
        $this->assert($transporter);
        $service->saveAddress($transporter, null, $this->validated($request), $request);
        return redirect()->route('admin.transporters.edit', $transporter)->with('message', 'Transporter address added successfully.');
    }
    public function edit(Individual $transporter, IndividualAddress $address): View
    {
        $this->assert($transporter);
        abort_unless((int)$address->individual_id === (int)$transporter->id, 404);
        return view('admin.transporters.address-form', ['transporter' => $transporter,'address' => $address,'states' => DB::table('states')->where('status', 'Active')->orderBy('name')->get()]);
    }
    public function update(Request $request, Individual $transporter, IndividualAddress $address, TransporterMasterService $service): RedirectResponse
    {
        $this->assert($transporter);
        $service->saveAddress($transporter, $address, $this->validated($request), $request);
        return redirect()->route('admin.transporters.edit', $transporter)->with('message', 'Transporter address updated successfully.');
    }
    public function destroy(Request $request, Individual $transporter, IndividualAddress $address, TransporterMasterService $service): RedirectResponse
    {
        $this->assert($transporter);
        $service->removeAddress($transporter, $address, $request);
        return back()->with('message', 'Transporter address removed successfully.');
    }
    private function validated(Request $request): array
    {
        return $request->validate(['address_type' => 'required|in:b,s','address_1' => 'required|string|max:5555','address_2' => 'required|string|max:5555','state_id' => 'required|integer','city' => 'required|string|max:255','zip_code' => 'required|string|max:10','default_address' => 'nullable|boolean']);
    }
    private function assert(Individual $transporter): void
    {
        abort_if($transporter->type !== 'transport' || $transporter->status === 'Deleted', 404);
    }
}
