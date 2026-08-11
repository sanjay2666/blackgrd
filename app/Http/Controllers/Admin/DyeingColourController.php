<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\Colour;
use App\Models\DyeingColour;
use App\Rules\RecordStatusRule;
use App\Services\DyeingColourMasterService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DyeingColourController extends Controller
{
    public function index(Request $request): View
    {
        $query = DyeingColour::query()->with('colour')->notDeleted();
        $qsearch = trim((string) $request->input('qsearch', ''));
        if ($qsearch !== '') {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$qsearch.'%')->orWhere('code', 'like', '%'.$qsearch.'%'));
        }
        if ($request->filled('colour_id')) {
            $query->where('colour_id', (int) $request->input('colour_id'));
        }
        if ($request->filled('status') && in_array($request->input('status'), ['Active', 'Inactive'], true)) {
            $query->where('status', $request->input('status'));
        }
        $shades = $query->orderBy('display_order')->orderBy('id')->paginate(config('app.pagination_limit'))->withQueryString();
        $colours = Colour::query()->active()->orderBy('name')->get(['id', 'name']);

        return view('admin.dyeing-colours.index', compact('shades', 'colours', 'qsearch'))->with('statusOptions', RecordStatus::formOptions());
    }

    public function create(): View
    {
        return view('admin.dyeing-colours.create', ['colours' => Colour::query()->active()->orderBy('name')->get(['id', 'name']), 'statusOptions' => RecordStatus::formOptions()]);
    }

    public function store(Request $request, DyeingColourMasterService $service)
    {
        $validated = $request->validate($this->rules());
        $service->save(new DyeingColour(), $validated);

        return redirect()->route('admin.dyeing-colours.index')->with('message', 'Shade added successfully.')->with('messageClass', 'successClass');
    }

    public function edit($id): View
    {
        $shade = DyeingColour::whereKey(dec($id))->firstOrFail();
        abort_if($shade->status === 'Deleted', 404);

        return view('admin.dyeing-colours.edit', ['shade' => $shade, 'colours' => Colour::query()->active()->orderBy('name')->get(['id', 'name']), 'statusOptions' => RecordStatus::formOptions()]);
    }

    public function update(Request $request, $id, DyeingColourMasterService $service)
    {
        $shade = DyeingColour::whereKey(dec($id))->firstOrFail();
        abort_if($shade->status === 'Deleted', 404);
        $service->save($shade, $request->validate($this->rules()));

        return redirect()->route('admin.dyeing-colours.index')->with('message', 'Shade updated successfully.')->with('messageClass', 'successClass');
    }

    public function destroy($id, DyeingColourMasterService $service): never
    {
        $shade = DyeingColour::whereKey(dec($id))->firstOrFail();
        $service->rejectDeletion($shade);
    }

    public function activate($id, DyeingColourMasterService $service)
    {
        $shade = DyeingColour::whereKey(dec($id))->firstOrFail();
        abort_if($shade->status === 'Deleted', 404);
        $service->transition($shade, 'Active');

        return back();
    }

    public function deactivate($id, DyeingColourMasterService $service)
    {
        $shade = DyeingColour::whereKey(dec($id))->firstOrFail();
        abort_if($shade->status === 'Deleted', 404);
        $service->transition($shade, 'Inactive');

        return back();
    }

    /** @return array<string, mixed> */
    private function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/'],
            'colour_id' => 'required|integer',
            'description' => 'nullable|string|max:5000',
            'display_order' => 'nullable|integer|min:0|max:2147483647',
            'status' => ['required', new RecordStatusRule()],
        ];
    }
}
