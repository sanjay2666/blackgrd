<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AllPage;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class AllPageController extends Controller
{
    public function index(Request $request)
    {
        $query = AllPage::query();
        if ($request->filled('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('page_title', 'like', '%'.$request->search.'%');
                $query->orWhere('page_name', 'like', '%'.$request->search.'%');
                $query->orWhere('model_name', 'like', '%'.$request->search.'%');
            });
        }

        $allPages = $query->orderBy('id', 'desc')->paginate(config('app.pagination_limit'))->withQueryString();

        return view('admin.all_pages.index', compact('allPages'));
    }

    public function create()
    {
        return view('admin.all_pages.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'model_name' => 'nullable',
            'page_title' => 'required',
            'page_name' => 'required',
            'page_rank' => 'required',
            'status' => 'required|in:0,1',
        ], [
            'page_title.required' => 'Please enter Page Title.',
            'page_name.required' => 'Please enter Page Name.',
            'page_rank.required' => 'Please enter Page Rank.',
            'status.required' => 'Please select Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $allPage = new AllPage();
            $allPage->model_name = $request->model_name;
            $allPage->page_title = $request->page_title;
            $allPage->page_name = $request->page_name;
            $allPage->page_rank = $request->page_rank;
            $allPage->status = $request->status;
            $allPage->save();

            DB::commit();
            Session::put('message', 'All Pages added successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.all-pages.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to save All Pages. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function edit($id)
    {
        $id = dec($id);
        $allPage = AllPage::where('id', $id)->firstOrFail();
        return view('admin.all_pages.edit', compact('allPage'));
    }

    public function update(Request $request, $id)
    {
        $id = dec($id);
        $allPage = AllPage::where('id', $id)->firstOrFail();
        $validator = Validator::make($request->all(), [
            'model_name' => 'nullable',
            'page_title' => 'required',
            'page_name' => 'required',
            'page_rank' => 'required',
            'status' => 'required|in:0,1',
        ], [
            'page_title.required' => 'Please enter Page Title.',
            'page_name.required' => 'Please enter Page Name.',
            'page_rank.required' => 'Please enter Page Rank.',
            'status.required' => 'Please select Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $allPage->model_name = $request->model_name;
            $allPage->page_title = $request->page_title;
            $allPage->page_name = $request->page_name;
            $allPage->page_rank = $request->page_rank;
            $allPage->status = $request->status;
            $allPage->save();

            DB::commit();
            Session::put('message', 'All Pages updated successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.all-pages.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to update All Pages. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        $id = dec($id);
        $allPage = AllPage::where('id', $id)->firstOrFail();
        DB::beginTransaction();
        try {
            $allPage->save();

            DB::commit();
            Session::put('message', 'All Pages deleted successfully.');
            Session::put('messageClass', 'successClass');

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to delete All Pages. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}

