<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\Colour;
use App\Rules\RecordStatusRule;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Services\ColourMasterService;

class ColourController extends Controller
{
    public function index(Request $request): View
    {
        $query = Colour::query();
        $query->notDeleted();

        $qsearch = trim((string) $request->input('qsearch', ''));
        if ($qsearch == '' && $request->filled('search')) {
            $qsearch = trim($request->input('search', ''));
        }

        if ($qsearch != '') {
            $query->where(function ($query) use ($qsearch) {
                $query->where('name', 'like', '%'.$qsearch.'%');
                $query->orWhere('code', 'like', '%'.$qsearch.'%');
            });
        }

        if ($request->filled('status') && in_array($request->input('status'), ['Active', 'Inactive'], true)) {
            $query->where('status', $request->input('status'));
        }
        $colours = $query->orderBy('id')->paginate(config('app.pagination_limit'))->withQueryString();

        return view('admin.colours.index', compact('colours', 'qsearch'))->with('statusOptions', RecordStatus::formOptions());
    }

    public function create()
    {
        return view('admin.colours.create', ['statusOptions' => RecordStatus::formOptions()]);
    }

    public function store(Request $request, ColourMasterService $service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/'],
            'status' => ['required', new RecordStatusRule],
        ]);
        $service->save(new Colour(), $validated);
        return redirect()->route('admin.colours.index')->with('message', 'Colour added successfully.')->with('messageClass', 'successClass');
    }

    public function edit($id)
    {
        $id = dec($id);
        $colour = Colour::where('id', $id)->firstOrFail();
        if ($colour->status === 'Deleted') {
            abort(404);
        }

        return view('admin.colours.edit', compact('colour'))->with('statusOptions', RecordStatus::formOptions());
    }

    public function update(Request $request, $id, ColourMasterService $service)
    {
        $id = dec($id);
        $colour = Colour::where('id', $id)->firstOrFail();
        if ($colour->status === 'Deleted') {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/'],
            'status' => ['required', new RecordStatusRule],
        ]);
        $service->save($colour, $validated);
        return redirect()->route('admin.colours.index')->with('message', 'Colour updated successfully.')->with('messageClass', 'successClass');
    }

    public function destroy($id, ColourMasterService $service)
    {
        $id = dec($id);
        $colour = Colour::where('id', $id)->firstOrFail();
        if ($colour->status === 'Deleted') {
            abort(404);
        }

        $service->rejectDeletion($colour);
    }

    public function activate($id, ColourMasterService $service)
    {
        $colour = Colour::whereKey(dec($id))->firstOrFail();
        abort_if($colour->status === 'Deleted', 404);
        $service->transition($colour, 'Active');
        return back();
    }

    public function deactivate($id, ColourMasterService $service)
    {
        $colour = Colour::whereKey(dec($id))->firstOrFail();
        abort_if($colour->status === 'Deleted', 404);
        $service->transition($colour, 'Inactive');
        return back();
    }
}
