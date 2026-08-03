<!DOCTYPE html>
<html lang="en">
<head>
@include('admin.common.head')
</head>
<body class="hold-transition sidebar-mini">
    <div id="preloader"><div id="status"></div></div>
    <div class="wrapper">
        @include('admin.common.header')
        @include('admin.common.sidebar')
        <div class="content-wrapper">
            <section class="content-header"><div class="header-icon"><i class="fa fa-list"></i></div><div class="header-title"><h1>Add Items</h1><small>Items</small></div></section>
            <section class="content"><div class="row"><div class="col-sm-12"><div class="panel panel-bd lobidrag">
                <div class="panel-heading"><a href="{{ route('admin.items.index') }}" class="btn btn-add"><i class="fa fa-list"></i> Items List</a></div>
                <div class="panel-body">
                    {!! display_message('message') !!}
                    <form method="POST" action="{{ route('admin.items.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-sm-4"><div class="form-group"><label>Item Name</label><input type="text" name="item_name" value="{{ old('item_name') }}" class="form-control"></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Item Code</label><input type="text" name="item_code" value="{{ old('item_code') }}" class="form-control"></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Internal Item Name</label><input type="text" name="internal_item_name" value="{{ old('internal_item_name') }}" class="form-control"></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Unit Price</label><input type="number" name="unit_price" value="{{ old('unit_price') }}" class="form-control" step="any"></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>HSN Code</label><input type="text" name="hsncode" value="{{ old('hsncode') }}" class="form-control"></div></div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>Item Type <span class="required">*</span></label>
                                    <select name="item_type_id" class="form-control" required>
                                        <option value="">Select Item Type</option>
                                        @foreach ($itemTypes as $itemType)
                                            <option value="{{ $itemType->item_type_id }}" @selected((string) old('item_type_id') === (string) $itemType->item_type_id)>{{ $itemType->item_type_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>Unit Type <span class="required">*</span></label>
                                    <select name="unit_type_id" class="form-control" required>
                                        <option value="">Select Unit Type</option>
                                        @foreach ($unitTypes as $unitType)
                                            <option value="{{ $unitType->unit_type_id }}" @selected((string) old('unit_type_id') === (string) $unitType->unit_type_id)>{{ $unitType->unit_type_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-4"><div class="form-group"><label>Colour Category</label><input type="text" name="clr_category" value="{{ old('clr_category') }}" class="form-control"></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Cut</label><input type="number" name="cut" value="{{ old('cut') }}" class="form-control" step="any"></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Purchase Rate</label><input type="number" name="pur_rate" value="{{ old('pur_rate') }}" class="form-control" step="any"></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Sale Rate</label><input type="number" name="sale_rate" value="{{ old('sale_rate') }}" class="form-control" step="any"></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>IGST</label><input type="number" name="igst" value="{{ old('igst') }}" class="form-control" step="any"></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>SGST</label><input type="number" name="sgst" value="{{ old('sgst') }}" class="form-control" step="any"></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>CGST</label><input type="number" name="cgst" value="{{ old('cgst') }}" class="form-control" step="any"></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Sale IGST</label><input type="number" name="sale_igst" value="{{ old('sale_igst') }}" class="form-control" step="any"></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Sale CGST</label><input type="number" name="sale_cgst" value="{{ old('sale_cgst') }}" class="form-control" step="any"></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Sale SGST</label><input type="number" name="sale_sgst" value="{{ old('sale_sgst') }}" class="form-control" step="any"></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Item GSM</label><input type="number" name="item_gsm" value="{{ old('item_gsm') }}" class="form-control" step="any"></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Final GSM</label><input type="text" name="item_final_gsm" value="{{ old('item_final_gsm') }}" class="form-control"></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Item Width</label><input type="text" name="item_width" value="{{ old('item_width') }}" class="form-control"></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Final Width</label><input type="text" name="item_final_width" value="{{ old('item_final_width') }}" class="form-control"></div></div>
                            <div class="col-sm-6"><div class="form-group"><label>Remarks</label><textarea name="remarks" class="form-control" rows="3">{{ old('remarks') }}</textarea></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>&nbsp;</label><div class="checkbox"><label><input type="checkbox" name="is_conusmable" value="1" @checked(old('is_conusmable'))> Is Consumable</label></div></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>&nbsp;</label><div class="checkbox"><label><input type="checkbox" name="is_outsourced" value="1" @checked(old('is_outsourced'))> Is Outsourced</label></div></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>&nbsp;</label><div class="checkbox"><label><input type="checkbox" name="is_jobwork" value="1" @checked(old('is_jobwork'))> Is Jobwork</label></div></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Lab Test Required <span class="required">*</span></label><select name="is_lab_test_required" class="form-control" required><option value="Yes" @selected((string) old('is_lab_test_required', 'Yes') === 'Yes')>Yes</option><option value="No" @selected((string) old('is_lab_test_required', 'Yes') === 'No')>No</option></select></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="Active" @selected(old('status', 'Active') === 'Active')>Active</option><option value="Inactive" @selected(old('status', 'Active') === 'Inactive')>Inactive</option></select></div></div>
                        </div>
                        <div class="reset-button"><a href="{{ route('admin.items.index') }}" class="btn btn-warning">Cancel</a> <button type="submit" class="btn btn-success">Save</button></div>
                    </form>
                </div>
            </div></div></div></section>
        </div>
        @include('admin.common.footer')
    </div>
    @include('admin.common.formfooterscript')
</body>
</html>
