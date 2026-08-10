<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Factory;
use App\Services\BranchFactoryMasterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use App\Services\CurrentOrganizationContext;

class BranchFactoryController extends Controller
{
    public function index(Request $request): View
    {
        $filter = fn ($query) => $query->notDeleted()
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));

        return view('admin.branches.index', [
            'branches' => $filter(Branch::query())->withCount('factories')->latest('id')->paginate(10, ['*'], 'branches_page')->withQueryString(),
            'factories' => $filter(Factory::query())->with('branch')->latest('id')->paginate(10, ['*'], 'factories_page')->withQueryString(),
        ]);
    }

    public function create(Request $request): View
    {
        $factory = $request->routeIs('admin.factories.*');
        $location = $factory ? new Factory(['status' => 'Active']) : new Branch(['status' => 'Active', 'kind' => 'commercial']);

        return view('admin.branches.form', ['location' => $location, 'locationType' => $factory ? 'factory' : 'branch', 'branches' => Branch::active()->orderBy('name')->get()]);
    }

    public function store(Request $request, BranchFactoryMasterService $service): RedirectResponse
    {
        [$location, $type] = $this->validatedLocation($request);
        $service->save($location, $request->all());

        return redirect()->route('admin.'.$type.'s.index')->with('message', ucfirst($type).' created successfully.')->with('messageClass', 'successClass');
    }

    public function editBranch(Branch $branch): View
    {
        return $this->edit($branch, 'branch');
    }

    public function editFactory(Factory $factory): View
    {
        return $this->edit($factory, 'factory');
    }

    public function updateBranch(Request $request, Branch $branch, BranchFactoryMasterService $service): RedirectResponse
    {
        $this->validateRequest($request, 'branch', $branch);
        $service->save($branch, $request->all());

        return redirect()->route('admin.branches.index')->with('message', 'Branch updated successfully.')->with('messageClass', 'successClass');
    }

    public function updateFactory(Request $request, Factory $factory, BranchFactoryMasterService $service): RedirectResponse
    {
        $this->validateRequest($request, 'factory', $factory);
        $service->save($factory, $request->all());

        return redirect()->route('admin.factories.index')->with('message', 'Factory updated successfully.')->with('messageClass', 'successClass');
    }

    public function activateBranch(Branch $branch, BranchFactoryMasterService $service): RedirectResponse
    {
        $service->transition($branch, 'Active');

        return back();
    }

    public function deactivateBranch(Branch $branch, BranchFactoryMasterService $service): RedirectResponse
    {
        $service->transition($branch, 'Inactive');

        return back();
    }

    public function activateFactory(Factory $factory, BranchFactoryMasterService $service): RedirectResponse
    {
        $service->transition($factory, 'Active');

        return back();
    }

    public function deactivateFactory(Factory $factory, BranchFactoryMasterService $service): RedirectResponse
    {
        $service->transition($factory, 'Inactive');

        return back();
    }

    private function edit($location, string $type): View
    {
        abort_if($location->status === 'Deleted', 404);

        return view('admin.branches.form', ['location' => $location, 'locationType' => $type, 'branches' => Branch::active()->orderBy('name')->get()]);
    }

    private function validatedLocation(Request $request): array
    {
        $type = $request->routeIs('admin.factories.*') ? 'factory' : 'branch';
        $location = $type === 'factory' ? new Factory() : new Branch();
        $this->validateRequest($request, $type, $location);

        return [$location, $type];
    }

    private function validateRequest(Request $request, string $type, $location): void
    {
        $table = $type === 'branch' ? 'branches' : 'factories';
        $code = $type === 'branch' ? 'branch_code' : 'factory_code';
        $rules = ['name' => 'required|string|max:150', 'status' => 'required|in:Active,Inactive', 'email' => 'nullable|email|max:150', 'gstin' => ['nullable', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/'], 'city' => 'nullable|string|max:100', 'state' => 'nullable|string|max:100', 'pin_code' => 'nullable|string|max:20'];
        $rules[$code] = 'required|string|max:30|alpha_dash|unique:'.$table.','.($location->id ?? 'NULL').',id,company_id,'.app(CurrentOrganizationContext::class)->companyId();
        $rules[$type === 'branch' ? 'kind' : 'branch_id'] = $type === 'branch' ? 'required|in:head_office,commercial,other' : 'nullable|integer|exists:branches,id';
        Validator::make($request->all(), $rules)->validate();
    }
}
