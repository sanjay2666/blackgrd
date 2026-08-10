<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NumberSeries;
use App\Services\NumberSeriesManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

final class NumberSeriesController extends Controller
{
    public function index(): View
    {
        return view('admin.number-series.index', ['series' => NumberSeries::query()->with('financialYear')->orderBy('document_name')->orderBy('financial_year_id')->get()]);
    }

    public function update(Request $request, NumberSeries $numberSeries, NumberSeriesManager $manager): RedirectResponse
    {
        $data = Validator::make($request->all(), [
            'prefix' => ['required', 'string', 'max:30'], 'suffix' => ['nullable', 'string', 'max:30'],
            'padding' => ['required', 'integer', 'min:0', 'max:20'], 'next_number' => ['required', 'integer', 'min:1'],
            'reset_policy' => ['required', 'in:never,financial_year'], 'status' => ['required', 'in:Active,Inactive'],
        ])->validate();
        $manager->update($numberSeries, $data);

        return back()->with('status', 'Number series updated successfully.');
    }
}
