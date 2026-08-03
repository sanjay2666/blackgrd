<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\State;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $company = Company::where('status', '!=', 'Deleted')->orderBy('id', 'asc')->first();

        return view('admin.companies.index', compact('company'));
    }

    public function create()
    {
        $company = Company::where('status', '!=', 'Deleted')->orderBy('id', 'asc')->first();

        if (! empty($company)) {
            return redirect()->route('admin.companies.edit', encrypt($company->id));
        }

        $states = State::where('status', 'Active')->orderBy('id', 'asc')->get();

        return view('admin.companies.create', compact('states'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'status' => 'required|in:Active,Inactive',
        ], [
            'name.required' => 'Please enter Company Name.',
            'status.required' => 'Please select Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $company = Company::where('status', '!=', 'Deleted')->orderBy('id', 'asc')->first();

            if (empty($company)) {
                $company = new Company;
                $company->created_by = Auth::guard('admin')->id();
                $company->created_at = now();
            }

            $company->company_code = $request->company_code;
            $company->name = $request->name;
            $company->legal_name = $request->legal_name;
            $company->trade_name = $request->trade_name;
            $company->company_description = $request->company_description;
            $company->registration_no = $request->registration_no;
            $company->pan_no = $request->pan_no;
            $company->tan_no = $request->tan_no;
            $company->gstin = $request->gstin;
            $company->email = $request->email;
            $company->alternate_email = $request->alternate_email;
            $company->phone = $request->phone;
            $company->alternate_phone = $request->alternate_phone;
            $company->mobile = $request->mobile;
            $company->whatsapp_no = $request->whatsapp_no;
            $company->website = $request->website;
            $company->contact_person_name = $request->contact_person_name;
            $company->contact_person_designation = $request->contact_person_designation;
            $company->contact_person_mobile = $request->contact_person_mobile;
            $company->contact_person_email = $request->contact_person_email;
            $company->address_1 = $request->address_1;
            $company->address_2 = $request->address_2;
            $company->landmark = $request->landmark;
            $state = State::where('id', $request->state_id)->first();
            $billingState = State::where('id', $request->billing_state_id)->first();

            $company->country_id = null;
            $company->state_id = $request->state_id;
            $company->state_name = ! empty($state) ? $state->name : null;
            $company->city_name = $request->city_name;
            $company->district_name = $request->district_name;
            $company->pincode = $request->pincode;
            $company->billing_address_1 = $request->billing_address_1;
            $company->billing_address_2 = $request->billing_address_2;
            $company->billing_country_id = null;
            $company->billing_state_id = $request->billing_state_id;
            $company->billing_state_name = ! empty($billingState) ? $billingState->name : null;
            $company->billing_city_name = $request->billing_city_name;
            $company->billing_pincode = $request->billing_pincode;
            $company->bank_name = $request->bank_name;
            $company->bank_branch_name = $request->bank_branch_name;
            $company->bank_account_holder_name = $request->bank_account_holder_name;
            $company->bank_account_no = $request->bank_account_no;
            $company->bank_account_type = $request->bank_account_type;
            $company->bank_ifsc_code = $request->bank_ifsc_code;
            $company->bank_micr_code = $request->bank_micr_code;
            $company->bank_swift_code = $request->bank_swift_code;
            $company->bank_upi_id = $request->bank_upi_id;
            $company->invoice_prefix = $request->invoice_prefix;
            $company->quotation_prefix = $request->quotation_prefix;
            $company->purchase_prefix = $request->purchase_prefix;
            $company->currency_code = $request->currency_code ?: 'INR';
            $company->currency_symbol = $request->currency_symbol ?: 'Rs';
            $company->timezone = $request->timezone ?: 'Asia/Kolkata';
            $company->date_format = $request->date_format ?: 'd-m-Y';
            $company->decimal_places = $request->decimal_places ?: 2;
            $company->default_tax_percentage = $request->default_tax_percentage ?: 0;
            $company->credit_limit = $request->credit_limit ?: 0;
            $company->default_credit_days = $request->default_credit_days ?: 0;
            $company->invoice_terms = $request->invoice_terms;
            $company->invoice_footer = $request->invoice_footer;
            $company->logo = $request->logo;
            $company->favicon = $request->favicon;
            $company->letterhead = $request->letterhead;
            $company->signature_image = $request->signature_image;
            $company->company_stamp = $request->company_stamp;
            $company->qr_code_image = $request->qr_code_image;
            $company->latitude = $request->latitude;
            $company->longitude = $request->longitude;
            $company->terms_and_conditions = $request->terms_and_conditions;
            $company->remarks = $request->remarks;
            $company->modified_by = Auth::guard('admin')->id();
            $company->updated_at = now();
            $company->status = $request->status;
            $company->save();

            DB::commit();
            Session::put('message', 'Company details saved successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.companies.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to save Company details. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function edit($id)
    {
        $id = dec($id);
        $company = Company::where('id', $id)->firstOrFail();

        if ($company->status === 'Deleted') {
            abort(404);
        }

        $states = State::where('status', 'Active')->orderBy('id', 'asc')->get();

        return view('admin.companies.edit', compact('company', 'states'));
    }

    public function update(Request $request, $id)
    {
        $id = dec($id);
        $company = Company::where('id', $id)->firstOrFail();

        if ($company->status === 'Deleted') {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'status' => 'required|in:Active,Inactive',
        ], [
            'name.required' => 'Please enter Company Name.',
            'status.required' => 'Please select Status.',
        ]);

        if ($validator->fails()) {
            Session::put('message', $validator->errors()->first());
            Session::put('messageClass', 'errorClass');

            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        try {
            $company->company_code = $request->company_code;
            $company->name = $request->name;
            $company->legal_name = $request->legal_name;
            $company->trade_name = $request->trade_name;
            $company->company_description = $request->company_description;
            $company->registration_no = $request->registration_no;
            $company->pan_no = $request->pan_no;
            $company->tan_no = $request->tan_no;
            $company->gstin = $request->gstin;
            $company->email = $request->email;
            $company->alternate_email = $request->alternate_email;
            $company->phone = $request->phone;
            $company->alternate_phone = $request->alternate_phone;
            $company->mobile = $request->mobile;
            $company->whatsapp_no = $request->whatsapp_no;
            $company->website = $request->website;
            $company->contact_person_name = $request->contact_person_name;
            $company->contact_person_designation = $request->contact_person_designation;
            $company->contact_person_mobile = $request->contact_person_mobile;
            $company->contact_person_email = $request->contact_person_email;
            $company->address_1 = $request->address_1;
            $company->address_2 = $request->address_2;
            $company->landmark = $request->landmark;
            $state = State::where('id', $request->state_id)->first();
            $billingState = State::where('id', $request->billing_state_id)->first();

            $company->country_id = null;
            $company->state_id = $request->state_id;
            $company->state_name = ! empty($state) ? $state->name : null;
            $company->city_name = $request->city_name;
            $company->district_name = $request->district_name;
            $company->pincode = $request->pincode;
            $company->billing_address_1 = $request->billing_address_1;
            $company->billing_address_2 = $request->billing_address_2;
            $company->billing_country_id = null;
            $company->billing_state_id = $request->billing_state_id;
            $company->billing_state_name = ! empty($billingState) ? $billingState->name : null;
            $company->billing_city_name = $request->billing_city_name;
            $company->billing_pincode = $request->billing_pincode;
            $company->bank_name = $request->bank_name;
            $company->bank_branch_name = $request->bank_branch_name;
            $company->bank_account_holder_name = $request->bank_account_holder_name;
            $company->bank_account_no = $request->bank_account_no;
            $company->bank_account_type = $request->bank_account_type;
            $company->bank_ifsc_code = $request->bank_ifsc_code;
            $company->bank_micr_code = $request->bank_micr_code;
            $company->bank_swift_code = $request->bank_swift_code;
            $company->bank_upi_id = $request->bank_upi_id;
            $company->invoice_prefix = $request->invoice_prefix;
            $company->quotation_prefix = $request->quotation_prefix;
            $company->purchase_prefix = $request->purchase_prefix;
            $company->currency_code = $request->currency_code ?: 'INR';
            $company->currency_symbol = $request->currency_symbol ?: 'Rs';
            $company->timezone = $request->timezone ?: 'Asia/Kolkata';
            $company->date_format = $request->date_format ?: 'd-m-Y';
            $company->decimal_places = $request->decimal_places ?: 2;
            $company->default_tax_percentage = $request->default_tax_percentage ?: 0;
            $company->credit_limit = $request->credit_limit ?: 0;
            $company->default_credit_days = $request->default_credit_days ?: 0;
            $company->invoice_terms = $request->invoice_terms;
            $company->invoice_footer = $request->invoice_footer;
            $company->logo = $request->logo;
            $company->favicon = $request->favicon;
            $company->letterhead = $request->letterhead;
            $company->signature_image = $request->signature_image;
            $company->company_stamp = $request->company_stamp;
            $company->qr_code_image = $request->qr_code_image;
            $company->latitude = $request->latitude;
            $company->longitude = $request->longitude;
            $company->terms_and_conditions = $request->terms_and_conditions;
            $company->remarks = $request->remarks;
            $company->modified_by = Auth::guard('admin')->id();
            $company->updated_at = now();
            $company->status = $request->status;
            $company->save();

            DB::commit();
            Session::put('message', 'Company details updated successfully.');
            Session::put('messageClass', 'successClass');

            return redirect()->route('admin.companies.index');
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to update Company details. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return back()->withInput();
        }
    }

    public function destroy($id)
    {
        $id = dec($id);
        $company = Company::where('id', $id)->firstOrFail();

        if ($company->status === 'Deleted') {
            abort(404);
        }

        DB::beginTransaction();
        try {
            $company->status = 'Deleted';
            $company->modified_by = Auth::guard('admin')->id();
            $company->updated_at = now();
            $company->save();

            DB::commit();
            Session::put('message', 'Company details deleted successfully.');
            Session::put('messageClass', 'successClass');

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            Session::put('message', 'Failed to delete Company details. Error: '.$e->getMessage());
            Session::put('messageClass', 'errorClass');

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}

