<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserWebPage;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class UserWebPageController extends Controller
{
    public function index(Request $request)
    {
        $query = UserWebPage::query();
        $query->where('status', '!=', 'Deleted');

        if ($request->filled('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('user_id', 'like', '%'.$request->search.'%');
                $query->orWhere('page_id', 'like', '%'.$request->search.'%');
            });
        }

        $userWebPages = $query->orderBy('id', 'desc')->paginate(config('app.pagination_limit'))->withQueryString();

        return view('admin.user_web_pages.index', compact('userWebPages'));
    }

    public function create()
    {
        return view('admin.user_web_pages.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'page_id' => 'required',
            'status' => 'required|in:Active,Inactive',
        ], [
            'user_id.required' => 'Please enter User Id.',
            'page_id.required' => 'Please enter Page Id.',
            'status.required' => 'Please select Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $userWebPage = new UserWebPage();
            $userWebPage->user_id = $request->user_id;
            $userWebPage->page_id = $request->page_id;
            $userWebPage->status = $request->status;
            $userWebPage->created = now();
            $userWebPage->modified = now();
            $userWebPage->save();

            DB::commit();
            Session::put('message', 'User Web Pages added successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.user-web-pages.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to save User Web Pages. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function edit($id)
    {
        $id = dec($id);
        $userWebPage = UserWebPage::where('id', $id)->firstOrFail();
        if ($userWebPage->status === 'Deleted') {
            abort(404);
        }

        return view('admin.user_web_pages.edit', compact('userWebPage'));
    }

    public function update(Request $request, $id)
    {
        $id = dec($id);
        $userWebPage = UserWebPage::where('id', $id)->firstOrFail();
        if ($userWebPage->status === 'Deleted') {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'page_id' => 'required',
            'status' => 'required|in:Active,Inactive',
        ], [
            'user_id.required' => 'Please enter User Id.',
            'page_id.required' => 'Please enter Page Id.',
            'status.required' => 'Please select Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $userWebPage->user_id = $request->user_id;
            $userWebPage->page_id = $request->page_id;
            $userWebPage->status = $request->status;
            $userWebPage->modified = now();
            $userWebPage->save();

            DB::commit();
            Session::put('message', 'User Web Pages updated successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.user-web-pages.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to update User Web Pages. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        $id = dec($id);
        $userWebPage = UserWebPage::where('id', $id)->firstOrFail();
        if ($userWebPage->status === 'Deleted') {
            abort(404);
        }

        DB::beginTransaction();
        try {
            $userWebPage->status = 'Deleted';
            $userWebPage->modified = now();
            $userWebPage->save();

            DB::commit();
            Session::put('message', 'User Web Pages deleted successfully.');
            Session::put('messageClass', 'successClass');

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to delete User Web Pages. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}

