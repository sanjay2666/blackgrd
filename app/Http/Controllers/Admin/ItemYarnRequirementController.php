<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemYarnRequirement;
use App\Models\ProcessItem;
use App\Services\YarnRecipeService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ItemYarnRequirementController extends Controller
{
    public function __construct(private readonly YarnRecipeService $recipes)
    {
    }

    public function index(Request $request)
    {
        $query = ItemYarnRequirement::query()->with(['item', 'yarnItem', 'process'])->where('status', '!=', 'Deleted');

        if ($request->filled('search')) {
            $query->where(function ($query) use ($request) {
                $term = trim($request->input('search'));
                $query->where('unit', 'like', "%{$term}%")
                    ->orWhereHas('item', fn ($q) => $q->where('item_name', 'like', "%{$term}%"))
                    ->orWhereHas('yarnItem', fn ($q) => $q->where('item_name', 'like', "%{$term}%"))
                    ->orWhereHas('process', fn ($q) => $q->where('process_name', 'like', "%{$term}%"));
            });
        }

        $itemYarnRequirements = $query->orderBy('id', 'desc')->paginate(config('app.pagination_limit'))->withQueryString();

        return view('admin.item_yarn_requirements.index', compact('itemYarnRequirements'));
    }

    public function create()
    {
        return view('admin.item_yarn_requirements.create', $this->formOptions());
    }

    public function store(Request $request)
    {
        try {
            $this->recipes->save(new ItemYarnRequirement(), $this->validated($request), $request);

            return redirect()->route('admin.item-yarn-requirements.index')->with('message', 'Yarn recipe added successfully.')->with('messageClass', 'successClass');
        } catch (ValidationException|Exception $exception) {
            return back()->withInput()->withErrors($exception instanceof ValidationException ? $exception->errors() : ['recipe' => $exception->getMessage()]);
        }
    }

    public function edit($id)
    {
        $id = dec($id);
        $itemYarnRequirement = ItemYarnRequirement::with('yarnItem')->where('id', $id)->where('status', '!=', 'Deleted')->firstOrFail();

        return view('admin.item_yarn_requirements.edit', array_merge(compact('itemYarnRequirement'), $this->formOptions($itemYarnRequirement)));
    }

    public function update(Request $request, $id)
    {
        $id = dec($id);
        $itemYarnRequirement = ItemYarnRequirement::where('id', $id)->where('status', '!=', 'Deleted')->firstOrFail();
        try {
            $this->recipes->save($itemYarnRequirement, $this->validated($request), $request);

            return redirect()->route('admin.item-yarn-requirements.index')->with('message', 'Yarn recipe updated successfully.')->with('messageClass', 'successClass');
        } catch (ValidationException|Exception $exception) {
            return back()->withInput()->withErrors($exception instanceof ValidationException ? $exception->errors() : ['recipe' => $exception->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $id = dec($id);
        $itemYarnRequirement = ItemYarnRequirement::where('id', $id)->where('status', '!=', 'Deleted')->firstOrFail();
        try {
            $this->recipes->remove($itemYarnRequirement, request());

            return response()->json(['success' => true]);
        } catch (Exception $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 500);
        }
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'item_id' => ['required', 'integer'], 'yarn_id' => ['required', 'integer'], 'process_id' => ['required', 'integer'],
            'reed_peak' => ['required', 'numeric', 'min:0', 'max:999999'], 'yarn_quantity' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'unit' => ['required', 'string', 'max:22'], 'status' => ['required', 'in:Active,Inactive'],
        ]);
    }

    private function formOptions(?ItemYarnRequirement $current = null): array
    {
        $items = Item::query()->active()->orderBy('item_name')->get();
        $yarns = $this->recipes->activeYarns();
        if ($current?->yarn_id && ! $yarns->contains('item_id', $current->yarn_id)) {
            $yarns->push($current->yarnItem);
        }
        $processes = ProcessItem::query()->notDeleted()->orderBy('process_name')->get();

        return compact('items', 'yarns', 'processes');
    }
}
