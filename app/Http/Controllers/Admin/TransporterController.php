<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\Individual;
use App\Services\TransporterMasterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransporterController extends Controller
{
    public function index(Request $request): View
    {
        $query = Individual::where('type', 'transport')->where('status', '!=', 'Deleted')->with('activeAddresses')->when($request->filled('search'), fn ($q) => $q->where(fn ($s) => $s->where('name', 'like', '%'.$request->string('search').'%')->orWhere('transporter_code', 'like', '%'.$request->string('search').'%')->orWhere('gstin', 'like', '%'.$request->string('search').'%')->orWhere('phone', 'like', '%'.$request->string('search').'%')))->when(in_array($request->status, ['Active','Inactive'], true), fn ($q) => $q->where('status', $request->status));
        return view('admin.transporters.index', ['transporters' => $query->latest('id')->paginate(config('app.pagination_limit'))->withQueryString()]);
    }
    public function create(): View
    {
        return view('admin.transporters.create');
    }
    public function store(Request $request, TransporterMasterService $service): RedirectResponse
    {
        $service->save(new Individual(), $this->validated($request), $request);
        return redirect()->route('admin.transporters.index')->with('message', 'Transporter added successfully.')->with('messageClass', 'successClass');
    }
    public function edit(Individual $transporter): View
    {
        $this->assertTransporter($transporter);
        return view('admin.transporters.edit', compact('transporter'));
    }
    public function update(Request $request, Individual $transporter, TransporterMasterService $service): RedirectResponse
    {
        $this->assertTransporter($transporter);
        $service->save($transporter, $this->validated($request), $request);
        return redirect()->route('admin.transporters.edit', $transporter)->with('message', 'Transporter updated successfully.')->with('messageClass', 'successClass');
    }
    public function activate(Individual $transporter, TransporterMasterService $service): RedirectResponse
    {
        $this->assertTransporter($transporter);
        $service->transition($transporter, 'Active');
        return back()->with('message', 'Transporter activated successfully.');
    }
    public function deactivate(Individual $transporter, TransporterMasterService $service): RedirectResponse
    {
        $this->assertTransporter($transporter);
        $service->transition($transporter, 'Inactive');
        return back()->with('message', 'Transporter deactivated successfully.');
    }
    public function destroy(Request $request, Individual $transporter, TransporterMasterService $service): RedirectResponse
    {
        $this->assertTransporter($transporter);
        $service->remove($transporter, $request);
        return back()->with('message', 'Transporter removed successfully.');
    }
    private function validated(Request $request): array
    {
        return $request->validate(['name' => 'required|string|max:255', 'transporter_code' => 'nullable|string|max:50', 'company_name' => 'nullable|string|max:100', 'gstin' => 'nullable|string|max:20', 'pan' => 'nullable|string|max:20', 'phone' => 'nullable|string|max:25', 'whatsapp' => 'nullable|string|max:25', 'email' => 'nullable|email|max:100', 'status' => 'required|in:Active,Inactive']);
    }
    private function assertTransporter(Individual $transporter): void
    {
        abort_if($transporter->type !== 'transport' || $transporter->status === RecordStatus::Deleted->value, 404);
    }
}
