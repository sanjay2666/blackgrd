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
            <section class="content-header"><div class="header-icon"><i class="fa fa-list"></i></div><div class="header-title"><h1>Add Item Yarn Requirements</h1><small>Item Yarn Requirements</small></div></section>
            <section class="content"><div class="row"><div class="col-sm-12"><div class="panel panel-bd lobidrag">
                <div class="panel-heading"><a href="{{ route('admin.item-yarn-requirements.index') }}" class="btn btn-add"><i class="fa fa-list"></i> Item Yarn Requirements List</a></div>
                <div class="panel-body">
                    {!! display_message('message') !!}
                    <form method="POST" action="{{ route('admin.item-yarn-requirements.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-sm-4"><div class="form-group"><label>Item Id <span class="required">*</span></label><input type="number" name="item_id" value="{{ old('item_id') }}" class="form-control" step="any" required></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Yarn Id <span class="required">*</span></label><input type="number" name="yarn_id" value="{{ old('yarn_id') }}" class="form-control" step="any" required></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Reed Peak <span class="required">*</span></label><input type="number" name="reed_peak" value="{{ old('reed_peak') }}" class="form-control" step="any" required></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Yarn Quantity</label><input type="number" name="yarn_quantity" value="{{ old('yarn_quantity') }}" class="form-control" step="any"></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Unit <span class="required">*</span></label><input type="text" name="unit" value="{{ old('unit') }}" class="form-control" required></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Process Id <span class="required">*</span></label><input type="number" name="process_id" value="{{ old('process_id') }}" class="form-control" step="any" required></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="Active" @selected(old('status', 'Active') === 'Active')>Active</option><option value="Inactive" @selected(old('status', 'Active') === 'Inactive')>Inactive</option></select></div></div>
                        </div>
                        <div class="reset-button"><a href="{{ route('admin.item-yarn-requirements.index') }}" class="btn btn-warning">Cancel</a> <button type="submit" class="btn btn-success">Save</button></div>
                    </form>
                </div>
            </div></div></div></section>
        </div>
        @include('admin.common.footer')
    </div>
    @include('admin.common.formfooterscript')
</body>
</html>
