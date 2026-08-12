<!DOCTYPE html>
<html lang="en">
<head>@include('admin.common.head')</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    @include('admin.common.header')
    @include('admin.common.sidebar')
    <div class="content-wrapper">
        <section class="content-header">
            <div class="header-icon"><i class="fa fa-exclamation-triangle"></i></div>
            <div class="header-title"><h1>{{ $reason->exists ? 'Edit' : 'Add' }} Fabric Fault Reason</h1><small>Inspection rejection and wastage reason</small></div>
        </section>
        <section class="content">
            <div class="panel panel-bd lobidrag">
                <div class="panel-heading"><a href="{{ route('admin.fabric-fault-reasons.index') }}" class="btn btn-add"><i class="fa fa-list"></i> Reason List</a></div>
                <div class="panel-body">
                    {!! display_message('message') !!}
                    <form method="POST" action="{{ $reason->exists ? route('admin.fabric-fault-reasons.update', enc($reason->id)) : route('admin.fabric-fault-reasons.store') }}">
                        @csrf
                        @if($reason->exists) @method('PUT') @endif
                        <div class="row">
                            <div class="col-sm-6"><div class="form-group"><label>Process <span class="required">*</span></label><select name="process_id" class="form-control" required><option value="">Select Process</option>@foreach($processes as $process)<option value="{{ $process->id }}" @selected(old('process_id', $reason->process_id) == $process->id)>{{ $process->process_name }}</option>@endforeach</select></div></div>
                            <div class="col-sm-6"><div class="form-group"><label>Reason <span class="required">*</span></label><input name="reason" class="form-control" maxlength="255" required value="{{ old('reason', $reason->reason) }}"></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Status</label><select name="status" class="form-control">@foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected(old('status', $reason->exists ? $reason->status : 'Active') === $value)>{{ $label }}</option>@endforeach</select></div></div>
                        </div>
                        <div class="reset-button"><a href="{{ route('admin.fabric-fault-reasons.index') }}" class="btn btn-warning">Cancel</a> <button class="btn btn-success">Save</button></div>
                    </form>
                </div>
            </div>
        </section>
    </div>
    @include('admin.common.footer')
</div>
@include('admin.common.formfooterscript')
</body>
</html>
