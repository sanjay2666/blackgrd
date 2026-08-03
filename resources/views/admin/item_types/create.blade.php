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
            <section class="content-header"><div class="header-icon"><i class="fa fa-list"></i></div><div class="header-title"><h1>Add Item Types</h1><small>Item Types</small></div></section>
            <section class="content"><div class="row"><div class="col-sm-12"><div class="panel panel-bd lobidrag">
                <div class="panel-heading"><a href="{{ route('admin.item-types.index') }}" class="btn btn-add"><i class="fa fa-list"></i> Item Types List</a></div>
                <div class="panel-body">
                    {!! display_message('message') !!}
                    <form method="POST" action="{{ route('admin.item-types.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-sm-4"><div class="form-group"><label>Item Type Name <span class="required">*</span></label><input type="text" name="item_type_name" value="{{ old('item_type_name') }}" class="form-control" required></div></div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>Unit Type</label>
                                    <select name="unit_type_id" class="form-control">
                                        <option value="">Select Unit Type</option>
                                        @foreach ($unitTypes as $unitType)
                                            <option value="{{ $unitType->unit_type_id }}" @selected((string) old('unit_type_id') === (string) $unitType->unit_type_id)>{{ $unitType->unit_type_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-4"><div class="form-group"><label>Is Purchase <span class="required">*</span></label><select name="is_purchase" class="form-control" required><option value="1" @selected((string) old('is_purchase', '1') === '1')>1</option><option value="0" @selected((string) old('is_purchase', '1') === '0')>0</option></select></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Is Work <span class="required">*</span></label><select name="is_work" class="form-control" required><option value="1" @selected((string) old('is_work', '1') === '1')>1</option><option value="0" @selected((string) old('is_work', '1') === '0')>0</option></select></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Is Department <span class="required">*</span></label><select name="is_department" class="form-control" required><option value="0" @selected((string) old('is_department', '0') === '0')>0</option><option value="1" @selected((string) old('is_department', '0') === '1')>1</option></select></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Status</label><select name="status" class="form-control">@include('admin.common.status-options')</select></div></div>
                        </div>
                        <div class="reset-button"><a href="{{ route('admin.item-types.index') }}" class="btn btn-warning">Cancel</a> <button type="submit" class="btn btn-success">Save</button></div>
                    </form>
                </div>
            </div></div></div></section>
        </div>
        @include('admin.common.footer')
    </div>
    @include('admin.common.formfooterscript')
</body>
</html>
