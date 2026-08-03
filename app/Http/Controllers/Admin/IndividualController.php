<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Individual;
use App\Models\IndividualAddress;
use App\Models\ProcessItem;
use App\Models\State;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class IndividualController extends Controller
{
    public function index(Request $request)
    {
        $types = ['customers', 'master', 'agents', 'labourer', 'vendors', 'transport', 'employee'];
        $query = Individual::with(['processItem', 'department'])->where('status', '!=', 'Deleted');

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('gstin', 'like', "%{$search}%");
            });
        }

        return view('admin.individuals.index', [
            'individuals' => $query->latest('id')->paginate(15)->withQueryString(),
            'types' => $types,
        ]);
    }

    public function create()
    {
        $types = ['customers', 'master', 'agents', 'labourer', 'vendors', 'transport', 'employee'];
        $vendorTypes = ['yarn', 'greige', 'chemical', 'maintanance', 'general'];
        $processItems = ProcessItem::where('status', 'Active')->orderBy('id', 'asc')->get();
        $departments = Department::where('status', 'Active')->orderBy('id', 'asc')->get();
        $states = State::where('status', 'Active')->orderBy('id', 'asc')->get();

        return view('admin.individuals.create', compact('types', 'vendorTypes', 'processItems', 'departments', 'states'));
    }

    public function edit($individual)
    {
        $individualId = dec($individual);

        $individual = Individual::with(['activeAddresses', 'processItem', 'department'])
            ->where('status', '!=', 'Deleted')
            ->findOrFail($individualId);

        $types = ['customers', 'master', 'agents', 'labourer', 'vendors', 'transport', 'employee'];
        $vendorTypes = ['yarn', 'greige', 'chemical', 'maintanance', 'general'];
        $processItems = ProcessItem::where('status', 'Active')->orderBy('id', 'asc')->get();
        $departments = Department::where('status', 'Active')->orderBy('id', 'asc')->get();
        $states = State::where('status', 'Active')->orderBy('id', 'asc')->get();

        return view('admin.individuals.edit', compact('individual', 'types', 'vendorTypes', 'processItems', 'departments', 'states'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'process_type_id' => 'nullable|integer|exists:process_items,id',
            'department_id' => 'nullable|integer|exists:departments,id',
            'name' => 'required|max:255',
            'type' => 'required|in:customers,master,agents,labourer,vendors,transport,employee',
            'vendor_type' => 'nullable|in:yarn,greige,chemical,maintanance,general',
            'phone' => 'required|digits:10',
            'company_name' => 'nullable|max:100',
            'nick_name' => 'nullable|max:255',
            'gstin' => 'nullable|required_if:type,customers,vendors|max:100',
            'pan' => 'nullable|max:100',
            'tanno' => 'nullable|max:11',
            'adhar' => 'nullable|max:100',
            'whatsapp' => 'required|digits:10',
            'email' => 'required|email|max:100|unique:individuals,email',
            'password' => 'required_if:type,employee,master|nullable|min:6',
            'is_lab_test_required' => 'required|in:Yes,No',
            'verified_remark' => 'nullable|max:100',
            'is_verified' => 'required|in:yes,no',
            'status' => 'required|in:Active,Inactive',
            'same_as_billing' => 'nullable|boolean',
            'billing.address_1' => 'required|max:5555',
            'billing.address_2' => 'required|max:5555',
            'billing.state_id' => 'required|integer|exists:states,id',
            'billing.city' => 'required|max:255',
            'billing.zip_code' => 'required|max:10',
            'shipping.address_1' => 'required_unless:same_as_billing,1|nullable|max:5555',
            'shipping.address_2' => 'required_unless:same_as_billing,1|nullable|max:5555',
            'shipping.state_id' => 'required_unless:same_as_billing,1|nullable|integer|exists:states,id',
            'shipping.city' => 'required_unless:same_as_billing,1|nullable|max:255',
            'shipping.zip_code' => 'required_unless:same_as_billing,1|nullable|max:10',
        ], [
            'name.required' => 'Please enter Name.',
            'type.required' => 'Please select Individual Type.',
            'type.in' => 'Please select valid Individual Type.',
            'vendor_type.in' => 'Please select valid Vendor Type.',
            'phone.required' => 'Please enter Mobile Number.',
            'phone.digits' => 'Mobile Number should be 10 digits.',
            'gstin.required_if' => 'Please enter GSTIN.',
            'whatsapp.required' => 'Please enter WhatsApp Number.',
            'whatsapp.digits' => 'WhatsApp Number should be 10 digits.',
            'email.required' => 'Please enter Email.',
            'email.email' => 'Please enter valid Email.',
            'email.unique' => 'Email Id already exists.',
            'password.required_if' => 'Please enter Password.',
            'password.min' => 'Password should be minimum 6 characters.',
            'is_lab_test_required.required' => 'Please select Lab Test Required.',
            'is_verified.required' => 'Please select Is Verified.',
            'status.required' => 'Please select Status.',
            'billing.address_1.required' => 'Please enter Billing Address 1.',
            'billing.address_2.required' => 'Please enter Billing Address 2.',
            'billing.state_id.required' => 'Please select Billing State.',
            'billing.city.required' => 'Please enter Billing City.',
            'billing.zip_code.required' => 'Please enter Billing Zip Code.',
            'shipping.address_1.required_unless' => 'Please enter Shipping Address 1.',
            'shipping.address_2.required_unless' => 'Please enter Shipping Address 2.',
            'shipping.state_id.required_unless' => 'Please select Shipping State.',
            'shipping.city.required_unless' => 'Please enter Shipping City.',
            'shipping.zip_code.required_unless' => 'Please enter Shipping Zip Code.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        $validated = $validator->validated();
        if (($request->input('type') === 'employee' || $request->input('type') === 'master') && User::where('email', '=', $request->email)->exists()) {
            Session::put('message', 'Email Id already exists.');
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }

        DB::beginTransaction();
        try {
            $now = now();
            $adminId = Auth::guard('admin')->id();

            $individual = new Individual();
            $individual->process_type_id = $validated['process_type_id'] ?? null;
            $individual->department_id = $validated['department_id'] ?? null;
            $individual->name = $validated['name'];
            $individual->type = $validated['type'];
            $individual->vendor_type = $validated['type'] === 'vendors' ? ($validated['vendor_type'] ?? null) : null;
            $individual->phone = $validated['phone'];
            $individual->company_name = $validated['company_name'] ?? null;
            $individual->nick_name = $validated['nick_name'] ?? null;
            $individual->gstin = $validated['gstin'] ?? null;
            $individual->pan = $validated['pan'] ?? null;
            $individual->tanno = $validated['tanno'] ?? null;
            $individual->adhar = $validated['adhar'] ?? null;
            $individual->whatsapp = $validated['whatsapp'];
            $individual->email = $validated['email'];
            $individual->is_lab_test_required = $validated['is_lab_test_required'];
            $individual->verified_remark = $validated['verified_remark'] ?? null;
            $individual->is_verified = $validated['is_verified'];
            $individual->created_at = $now;
            $individual->created_by = $adminId;
            $individual->status = $validated['status'];
            $isSaved = $individual->save();

            if (! $isSaved) {
                throw new Exception('Individual details not saved.');
            }

            $indId = $individual->id;
            $shipping = ! empty($validated['same_as_billing']) ? $validated['billing'] : $validated['shipping'];

            foreach (['b' => $validated['billing'], 's' => $shipping] as $addressType => $address) {
                $objAddress = new IndividualAddress();
                $objAddress->individual_id = $indId;
                $objAddress->address_type = $addressType;
                $objAddress->address_1 = $address['address_1'];
                $objAddress->address_2 = $address['address_2'];
                $objAddress->state_id = $address['state_id'];
                $objAddress->city = $address['city'];
                $objAddress->zip_code = $address['zip_code'];
                $objAddress->default_address = $addressType === 'b';
                $objAddress->created = $now;
                $objAddress->created_by = $adminId;
                $objAddress->status = 'Active';
                $objAddress->save();
            }

            if ($request->input('type') === 'employee' || $request->input('type') === 'master') {
                $objM = new User();
                $objM->user_type = 'User';
                $objM->name = $request->name;
                $objM->individual_id = $indId;
                $objM->email = $request->email;
                $objM->password = Hash::make($request->password);
                $objM->financial_year = currentFinancialYear();
                $objM->created_at = $now;
                $objM->updated_at = $now;
                $objM->status = 'Active';
                $objM->save();
            }

            DB::commit();

            Session::put('message', 'Individual details added successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.individuals.index');
        } catch (Exception $e) {
            DB::rollBack();

            Session::put('message', 'Failed to save individual details. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function update(Request $request, $individual)
    {
        $individualId = dec($individual);
        $individual = Individual::where('status', '!=', 'Deleted')->findOrFail($individualId);

        $validator = Validator::make($request->all(), [
            'process_type_id' => 'nullable|integer|exists:process_items,id',
            'department_id' => 'nullable|integer|exists:departments,id',
            'name' => 'required|max:255',
            'type' => 'required|in:customers,master,agents,labourer,vendors,transport,employee',
            'vendor_type' => 'nullable|in:yarn,greige,chemical,maintanance,general',
            'phone' => 'required|digits:10',
            'company_name' => 'nullable|max:100',
            'nick_name' => 'nullable|max:255',
            'gstin' => 'nullable|required_if:type,customers,vendors|max:100',
            'pan' => 'nullable|max:100',
            'tanno' => 'nullable|max:11',
            'adhar' => 'nullable|max:100',
            'whatsapp' => 'required|digits:10',
            'email' => 'required|email|max:100|unique:individuals,email,'.$individual->id,
            'password' => 'nullable|min:6',
            'is_lab_test_required' => 'required|in:Yes,No',
            'verified_remark' => 'nullable|max:100',
            'is_verified' => 'required|in:yes,no',
            'status' => 'required|in:Active,Inactive',
            'same_as_billing' => 'nullable|boolean',
            'billing.address_1' => 'required|max:5555',
            'billing.address_2' => 'required|max:5555',
            'billing.state_id' => 'required|integer|exists:states,id',
            'billing.city' => 'required|max:255',
            'billing.zip_code' => 'required|max:10',
            'shipping.address_1' => 'required_unless:same_as_billing,1|nullable|max:5555',
            'shipping.address_2' => 'required_unless:same_as_billing,1|nullable|max:5555',
            'shipping.state_id' => 'required_unless:same_as_billing,1|nullable|integer|exists:states,id',
            'shipping.city' => 'required_unless:same_as_billing,1|nullable|max:255',
            'shipping.zip_code' => 'required_unless:same_as_billing,1|nullable|max:10',
        ], [
            'name.required' => 'Please enter Name.',
            'type.required' => 'Please select Individual Type.',
            'type.in' => 'Please select valid Individual Type.',
            'vendor_type.in' => 'Please select valid Vendor Type.',
            'phone.required' => 'Please enter Mobile Number.',
            'phone.digits' => 'Mobile Number should be 10 digits.',
            'gstin.required_if' => 'Please enter GSTIN.',
            'whatsapp.required' => 'Please enter WhatsApp Number.',
            'whatsapp.digits' => 'WhatsApp Number should be 10 digits.',
            'email.required' => 'Please enter Email.',
            'email.email' => 'Please enter valid Email.',
            'email.unique' => 'Email Id already exists.',
            'password.min' => 'Password should be minimum 6 characters.',
            'is_lab_test_required.required' => 'Please select Lab Test Required.',
            'is_verified.required' => 'Please select Is Verified.',
            'status.required' => 'Please select Status.',
            'billing.address_1.required' => 'Please enter Billing Address 1.',
            'billing.address_2.required' => 'Please enter Billing Address 2.',
            'billing.state_id.required' => 'Please select Billing State.',
            'billing.city.required' => 'Please enter Billing City.',
            'billing.zip_code.required' => 'Please enter Billing Zip Code.',
            'shipping.address_1.required_unless' => 'Please enter Shipping Address 1.',
            'shipping.address_2.required_unless' => 'Please enter Shipping Address 2.',
            'shipping.state_id.required_unless' => 'Please select Shipping State.',
            'shipping.city.required_unless' => 'Please enter Shipping City.',
            'shipping.zip_code.required_unless' => 'Please enter Shipping Zip Code.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        $validated = $validator->validated();
        if ($request->input('type') === 'employee' || $request->input('type') === 'master') {
            $linkedUser = User::where('individual_id', $individual->id)->first();
            $emailExists = User::where('email', $request->email)
                ->when($linkedUser, fn ($query) => $query->where('id', '!=', $linkedUser->id))
                ->exists();

            if ($emailExists) {
                Session::put('message', 'Email Id already exists.');
                Session::put('messageClass', 'errorClass');

                return back()->withInput();
            }

            if (! $linkedUser && empty($request->password)) {
                Session::put('message', 'Please enter a password.');
                Session::put('messageClass', 'errorClass');

                return back()->withInput();
            }
        }

        DB::beginTransaction();
        try {
            $now = now();
            $adminId = Auth::guard('admin')->id();

            $individual->process_type_id = $validated['process_type_id'] ?? null;
            $individual->department_id = $validated['department_id'] ?? null;
            $individual->name = $validated['name'];
            $individual->type = $validated['type'];
            $individual->vendor_type = $validated['type'] === 'vendors' ? ($validated['vendor_type'] ?? null) : null;
            $individual->phone = $validated['phone'];
            $individual->company_name = $validated['company_name'] ?? null;
            $individual->nick_name = $validated['nick_name'] ?? null;
            $individual->gstin = $validated['gstin'] ?? null;
            $individual->pan = $validated['pan'] ?? null;
            $individual->tanno = $validated['tanno'] ?? null;
            $individual->adhar = $validated['adhar'] ?? null;
            $individual->whatsapp = $validated['whatsapp'];
            $individual->email = $validated['email'];
            $individual->is_lab_test_required = $validated['is_lab_test_required'];
            $individual->verified_remark = $validated['verified_remark'] ?? null;
            $individual->is_verified = $validated['is_verified'];
            $individual->modified_at = $now;
            $individual->modified_by = $adminId;
            $individual->status = $validated['status'];
            $individual->save();

            $individual->addresses()->where('status', 'Active')->update([
                'status' => 'Deleted',
                'modified_at' => $now,
                'modified_by' => $adminId,
            ]);

            $shipping = ! empty($validated['same_as_billing']) ? $validated['billing'] : $validated['shipping'];

            foreach (['b' => $validated['billing'], 's' => $shipping] as $addressType => $address) {
                $objAddress = new IndividualAddress();
                $objAddress->individual_id = $individual->id;
                $objAddress->address_type = $addressType;
                $objAddress->address_1 = $address['address_1'];
                $objAddress->address_2 = $address['address_2'];
                $objAddress->state_id = $address['state_id'];
                $objAddress->city = $address['city'];
                $objAddress->zip_code = $address['zip_code'];
                $objAddress->default_address = $addressType === 'b';
                $objAddress->created = $now;
                $objAddress->created_by = $adminId;
                $objAddress->status = 'Active';
                $objAddress->save();
            }

            if ($request->input('type') === 'employee' || $request->input('type') === 'master') {
                $user = User::where('individual_id', $individual->id)->first();
                if ($user) {
                    $user->name = $request->name;
                    $user->email = $request->email;
                    if (! empty($request->password)) {
                        $user->password = Hash::make($request->password);
                    }
                    $user->status = 'Active';
                    $user->updated_at = $now;
                    $user->save();
                } else {
                    $user = new User();
                    $user->user_type = 'User';
                    $user->name = $request->name;
                    $user->individual_id = $individual->id;
                    $user->email = $request->email;
                    $user->password = Hash::make($request->password);
                    $user->status = 'Active';
                    $user->created_at = $now;
                    $user->updated_at = $now;
                    $user->save();
                }
            } else {
                $user = User::where('individual_id', $individual->id)->first();
                if ($user) {
                    $user->status = 'Inactive';
                    $user->updated_at = $now;
                    $user->save();
                }
            }

            DB::commit();

            Session::put('message', 'Individual details updated successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.individuals.index');
        } catch (Exception $e) {
            DB::rollBack();

            Session::put('message', 'Failed to update individual details. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function destroy($individual)
    {
        $individualId = dec($individual);
        $individual = Individual::where('status', '!=', 'Deleted')->findOrFail($individualId);

        DB::beginTransaction();
        try {
            $now = now();
            $adminId = Auth::guard('admin')->id();

            $individual->status = 'Deleted';
            $individual->deleted_at = $now;
            $individual->modified_at = $now;
            $individual->modified_by = $adminId;
            $individual->save();

            $individual->addresses()->where('status', '!=', 'Deleted')->update([
                'status' => 'Deleted',
                'modified_at' => $now,
                'modified_by' => $adminId,
            ]);

            $user = User::where('individual_id', $individual->id)->first();
            if ($user) {
                $user->status = 'Inactive';
                $user->updated_at = $now;
                $user->save();
            }

            DB::commit();

            Session::put('message', 'Individual deleted successfully.');
            Session::put('messageClass', 'successClass');

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();

            Session::put('message', 'Failed to delete individual. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

}
