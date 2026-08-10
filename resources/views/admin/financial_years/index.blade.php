<!DOCTYPE html>
<html lang="en">
<head>@include('admin.common.head')</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    @include('admin.common.header') @include('admin.common.sidebar')
    <div class="content-wrapper"><section class="content-header"><div class="header-icon"><i class="fa fa-calendar"></i></div><div class="header-title"><h1>Financial Years</h1><small>Company accounting periods</small></div></section>
    <section class="content">{!! display_message('message') !!}
        @if ($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif
        <div class="panel panel-bd"><div class="panel-heading"><a href="{{ route('admin.financial-years.create') }}" class="btn btn-add"><i class="fa fa-plus"></i> Add Financial Year</a></div>
        <div class="panel-body"><div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Code</th><th>Financial Year</th><th>Start</th><th>End</th><th>Current</th><th>Status</th><th>Actions</th></tr></thead><tbody>
        @forelse ($years as $year)<tr><td>{{ $year->code }}</td><td>{{ $year->display_name }}</td><td>{{ optional($year->start_date)->format('d-m-Y') }}</td><td>{{ optional($year->end_date)->format('d-m-Y') }}</td><td>{{ $year->is_current ? 'Yes' : 'No' }}</td><td>{{ $year->status }}</td><td><a href="{{ route('admin.financial-years.edit', $year) }}" class="btn btn-xs btn-default"><i class="fa fa-pencil"></i></a>@if (!$year->is_current && $year->status === 'Active') <form action="{{ route('admin.financial-years.set-current', $year) }}" method="POST" style="display:inline">@csrf<button class="btn btn-xs btn-primary" type="submit">Set current</button></form>@endif</td></tr>@empty<tr><td colspan="7" class="text-center">No financial years found.</td></tr>@endforelse
        </tbody></table></div>{{ $years->links() }}</div></div>
    </section></div>
    @include('admin.common.footer')
</div>@include('admin.common.formfooterscript')
</body></html>
