<?php

namespace App\Http\Controllers;

use App\Models\DyeingColour;
use Illuminate\Http\Request;

final class DyeingColourLookupController extends Controller
{
    public function __invoke(Request $request)
    {
        $qsearch = trim((string) $request->input('term', ''));
        $query = DyeingColour::query()->with('colour')->where('status', 'Active');

        if ($qsearch !== '') {
            $query->where(function ($query) use ($qsearch): void {
                $query->where('name', 'like', '%'.$qsearch.'%')
                    ->orWhere('code', 'like', '%'.$qsearch.'%');
            });
        }

        return response()->json($query->orderBy('display_order')->orderBy('name')->limit(50)->get());
    }
}
