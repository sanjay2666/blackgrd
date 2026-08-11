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
            <section class="content-header"><div class="header-icon"><i class="fa fa-cogs"></i></div><div class="header-title"><h1>Add Process Item</h1><small>Process list</small></div></section>
            <section class="content"><div class="row"><div class="col-sm-12"><div class="panel panel-bd lobidrag">
                <div class="panel-heading"><a href="{{ route('admin.process-items.index') }}" class="btn btn-add"><i class="fa fa-list"></i> Process List</a></div>
                <div class="panel-body">
                    {!! display_message('message') !!}
                    @if ($errors->any())<div class="alert alert-danger"><strong>Please fix the errors below.</strong></div>@endif
                    <form method="POST" action="{{ route('admin.process-items.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-sm-6"><div class="form-group"><label>Entry Name</label><input type="text" name="entry_name" value="{{ old('entry_name') }}" class="form-control"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Process Name <span class="required">*</span></label><input type="text" name="process_name" value="{{ old('process_name') }}" class="form-control" required>@error('process_name')<span class="text-danger small">{{ $message }}</span>@enderror</div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Short Code <span class="required">*</span></label><input type="text" name="short_code" value="{{ old('short_code') }}" class="form-control" required>@error('short_code')<span class="text-danger small">{{ $message }}</span>@enderror</div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Department</label><select name="department_id" class="form-control"><option value="">Reusable / unassigned</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->department_name }}</option>@endforeach</select></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Display Order</label><input type="number" name="display_order" value="{{ old('display_order') }}" class="form-control" min="0"></div></div>
                            <div class="col-sm-6"><div class="form-group"><label>Output Name <span class="required">*</span></label><input type="text" name="output_name" value="{{ old('output_name') }}" class="form-control" required>@error('output_name')<span class="text-danger small">{{ $message }}</span>@enderror</div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Last Serial No</label><input type="number" name="process_sl_no_last" value="{{ old('process_sl_no_last', 0) }}" class="form-control" min="0"></div></div>
                            <div class="col-sm-3"><div class="form-group"><label>Status</label><select name="status" class="form-control">@include('admin.common.status-options')</select></div></div>
                        </div>
                        <div class="reset-button"><a href="{{ route('admin.process-items.index') }}" class="btn btn-warning">Cancel</a> <button type="submit" class="btn btn-success">Save</button></div>
                    </form>
                </div>
            </div></div></div></section>
        </div>
        @include('admin.common.footer')
    </div>
    @include('admin.common.formfooterscript')
</body>
</html>

