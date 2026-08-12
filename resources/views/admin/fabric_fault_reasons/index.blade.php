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
            <div class="header-title"><h1>Fabric Fault Reasons</h1><small>Inspection rejection and wastage reasons</small></div>
        </section>
        <section class="content">
            {!! display_message('message') !!}
            <div class="panel panel-bd lobidrag">
                <div class="panel-heading"><a href="{{ route('admin.fabric-fault-reasons.create') }}" class="btn btn-add"><i class="fa fa-plus"></i> Add Reason</a></div>
                <div class="panel-body">
                    <form method="GET" action="{{ route('admin.fabric-fault-reasons.index') }}" class="row" style="margin-bottom:10px">
                        <div class="col-sm-4"><input name="search" class="form-control" placeholder="Search reason" value="{{ request('search') }}"></div>
                        <div class="col-sm-3"><select name="process_id" class="form-control"><option value="">All Processes</option>@foreach($processes as $process)<option value="{{ $process->id }}" @selected((string) request('process_id') === (string) $process->id)>{{ $process->process_name }}</option>@endforeach</select></div>
                        <div class="col-sm-3"><select name="status" class="form-control"><option value="">All Statuses</option>@foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
                        <div class="col-sm-2"><button class="btn btn-add">Filter</button></div>
                    </form>
                    <div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Reason</th><th>Process</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                    @forelse($reasons as $reason)
                        <tr><td>{{ $reason->reason }}</td><td>{{ $reason->process->process_name ?? $reason->process_id }}</td><td>{{ $reason->status }}</td><td><a class="btn btn-xs btn-info" href="{{ route('admin.fabric-fault-reasons.edit', enc($reason->id)) }}">Edit</a> <form method="POST" action="{{ route($reason->status === 'Active' ? 'admin.fabric-fault-reasons.deactivate' : 'admin.fabric-fault-reasons.activate', enc($reason->id)) }}" style="display:inline">@csrf @method('PATCH')<button class="btn btn-xs {{ $reason->status === 'Active' ? 'btn-warning' : 'btn-success' }}">{{ $reason->status === 'Active' ? 'Deactivate' : 'Activate' }}</button></form></td></tr>
                    @empty
                        <tr><td colspan="4">No reasons found.</td></tr>
                    @endforelse
                    </tbody></table></div>
                    {{ $reasons->links('vendor.pagination.bootstrap-4') }}
                </div>
            </div>
        </section>
    </div>
    @include('admin.common.footer')
</div>
@include('admin.common.formfooterscript')
</body>
</html>
