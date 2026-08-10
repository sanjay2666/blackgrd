<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyProfileRequest;
use App\Models\State;
use App\Services\CompanyProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(CompanyProfileService $profile): View
    {
        return view('admin.companies.index', ['company' => $profile->canonical()]);
    }

    public function edit(CompanyProfileService $profile): View
    {
        return view('admin.companies.edit', [
            'company' => $profile->canonical(),
            'states' => State::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function update(CompanyProfileRequest $request, CompanyProfileService $profile): RedirectResponse
    {
        $profile->update($request->safe()->except('logo'), $request->file('logo'));

        return redirect()->route('admin.companies.index')->with('message', 'Company profile updated successfully.')->with('messageClass', 'successClass');
    }
}
