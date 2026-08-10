<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\FinancialYearRequest;
use App\Models\FinancialYear;
use App\Services\FinancialYearManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FinancialYearController extends Controller
{
    public function index(Request $request): View
    {
        $years = FinancialYear::notDeleted()->latest('start_date')->paginate(config('app.pagination_limit'))->withQueryString();

        return view('admin.financial_years.index', compact('years'));
    }

    public function create(): View
    {
        return view('admin.financial_years.create', ['statusOptions' => RecordStatus::formOptions()]);
    }

    public function store(FinancialYearRequest $request, FinancialYearManager $manager): RedirectResponse
    {
        try {
            $manager->save($request->validated() + ['is_current' => $request->boolean('is_current')]);

            return redirect()->route('admin.financial-years.index')->with('status', 'Financial year added successfully.');
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }
    }

    public function edit(FinancialYear $financialYear): View
    {
        abort_if($financialYear->status === 'Deleted', 404);

        return view('admin.financial_years.edit', ['financialYear' => $financialYear, 'statusOptions' => RecordStatus::formOptions()]);
    }

    public function update(FinancialYearRequest $request, FinancialYear $financialYear, FinancialYearManager $manager): RedirectResponse
    {
        abort_if($financialYear->status === 'Deleted', 404);
        try {
            $manager->save($request->validated() + ['is_current' => $financialYear->is_current || $request->boolean('is_current')], $financialYear);

            return redirect()->route('admin.financial-years.index')->with('status', 'Financial year updated successfully.');
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }
    }

    public function setCurrent(FinancialYear $financialYear, FinancialYearManager $manager): RedirectResponse
    {
        $manager->setCurrent($financialYear);

        return back()->with('status', 'Current financial year updated successfully.');
    }

    public function destroy(FinancialYear $financialYear): RedirectResponse
    {
        abort_if($financialYear->status === 'Deleted', 404);
        if ($financialYear->is_current) {
            return back()->withErrors(['financial_year' => 'The current financial year cannot be deleted.']);
        }
        $financialYear->status = 'Deleted';
        $financialYear->modified_by = Auth::guard('admin')->id();
        $financialYear->updated_at = now();
        $financialYear->save();
        Session::put('message', 'Financial year deleted successfully.');
        Session::put('messageClass', 'successClass');

        return back();
    }
}
