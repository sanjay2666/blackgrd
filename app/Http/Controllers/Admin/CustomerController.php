<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\Individual;
use App\Rules\RecordStatusRule;
use App\Services\CustomerMasterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $query = Individual::query()->where('type', 'customers')->where('status', '!=', RecordStatus::Deleted->value)->with('activeAddresses')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($s) => $s->where('name', 'like', '%'.$request->string('search').'%')->orWhere('customer_code', 'like', '%'.$request->string('search').'%')->orWhere('gstin', 'like', '%'.$request->string('search').'%')->orWhere('phone', 'like', '%'.$request->string('search').'%')))
            ->when(in_array($request->status, ['Active', 'Inactive'], true), fn ($q) => $q->where('status', $request->status));

        return view('admin.customers.index', ['customers' => $query->latest('id')->paginate(config('app.pagination_limit'))->withQueryString()]);
    }

    public function create(): View
    {
        return view('admin.customers.create');
    }

    public function store(Request $request, CustomerMasterService $service): RedirectResponse
    {
        $service->save(new Individual(), $this->validated($request), $request);

        return redirect()->route('admin.customers.index')->with('message', 'Customer added successfully.')->with('messageClass', 'successClass');
    }

    public function edit(Individual $customer): View
    {
        $this->assertCustomer($customer);

        return view('admin.customers.edit', compact('customer'));
    }

    public function update(Request $request, Individual $customer, CustomerMasterService $service): RedirectResponse
    {
        $this->assertCustomer($customer);
        $service->save($customer, $this->validated($request), $request);

        return redirect()->route('admin.customers.edit', $customer)->with('message', 'Customer updated successfully.')->with('messageClass', 'successClass');
    }

    public function activate(Individual $customer, CustomerMasterService $service): RedirectResponse
    {
        $this->assertCustomer($customer);
        $service->transition($customer, RecordStatus::Active->value);

        return back()->with('message', 'Customer activated successfully.');
    }

    public function deactivate(Individual $customer, CustomerMasterService $service): RedirectResponse
    {
        $this->assertCustomer($customer);
        $service->transition($customer, RecordStatus::Inactive->value);

        return back()->with('message', 'Customer deactivated successfully.');
    }

    public function destroy(Request $request, Individual $customer, CustomerMasterService $service): RedirectResponse
    {
        $this->assertCustomer($customer);
        $service->remove($customer, $request);

        return back()->with('message', 'Customer removed successfully.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate(['name' => 'required|string|max:255', 'customer_code' => 'nullable|string|max:50', 'company_name' => 'nullable|string|max:100', 'gstin' => 'nullable|string|max:20', 'pan' => 'nullable|string|max:20', 'phone' => 'nullable|string|max:25', 'whatsapp' => 'nullable|string|max:25', 'email' => 'nullable|email|max:100', 'status' => ['required', 'in:Active,Inactive', new RecordStatusRule()]]);
    }

    private function assertCustomer(Individual $customer): void
    {
        abort_if($customer->type !== 'customers' || $customer->status === RecordStatus::Deleted->value, 404);
    }
}
