<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Individual;
use App\Models\IndividualAddress;
use App\Services\CustomerMasterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerAddressController extends Controller
{
    public function create(Individual $customer): View
    {
        $this->assertCustomer($customer);

        return view('admin.customers.address-form', ['customer' => $customer, 'states' => DB::table('states')->where('status', 'Active')->orderBy('name')->get()]);
    }

    public function store(Request $request, Individual $customer, CustomerMasterService $service): RedirectResponse
    {
        $this->assertCustomer($customer);
        $service->saveAddress($customer, null, $this->validated($request), $request);

        return redirect()->route('admin.customers.edit', $customer)->with('message', 'Customer address added successfully.')->with('messageClass', 'successClass');
    }

    public function edit(Individual $customer, IndividualAddress $address): View
    {
        $this->assertCustomer($customer);
        abort_unless((int) $address->individual_id === (int) $customer->getKey(), 404);

        return view('admin.customers.address-form', ['customer' => $customer, 'address' => $address, 'states' => DB::table('states')->where('status', 'Active')->orderBy('name')->get()]);
    }

    public function update(Request $request, Individual $customer, IndividualAddress $address, CustomerMasterService $service): RedirectResponse
    {
        $this->assertCustomer($customer);
        $service->saveAddress($customer, $address, $this->validated($request), $request);

        return redirect()->route('admin.customers.edit', $customer)->with('message', 'Customer address updated successfully.')->with('messageClass', 'successClass');
    }

    public function destroy(Request $request, Individual $customer, IndividualAddress $address, CustomerMasterService $service): RedirectResponse
    {
        $this->assertCustomer($customer);
        $service->removeAddress($customer, $address, $request);

        return back()->with('message', 'Customer address removed successfully.')->with('messageClass', 'successClass');
    }

    private function validated(Request $request): array
    {
        return $request->validate(['address_type' => 'required|in:b,s', 'address_1' => 'required|string|max:5555', 'address_2' => 'required|string|max:5555', 'state_id' => 'required|integer', 'city' => 'required|string|max:255', 'zip_code' => 'required|string|max:10', 'default_address' => 'nullable|boolean']);
    }

    private function assertCustomer(Individual $customer): void
    {
        abort_if($customer->type !== 'customers' || $customer->status === 'Deleted', 404);
    }
}
