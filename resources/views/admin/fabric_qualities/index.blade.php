<!DOCTYPE html>
<html lang="en">
<head>@include('admin.common.head')</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">@include('admin.common.header') @include('admin.common.sidebar')
<div class="content-wrapper">
<section class="content-header"><div class="header-icon"><i class="fa fa-th-list"></i></div><div class="header-title"><h1>Fabric Quality Master</h1><small>Reusable fabric specification identity</small></div></section>
<section class="content">{!! display_message('message') !!}
<div class="panel panel-bd lobidrag"><div class="panel-heading"><h4>Fabric Quality List</h4></div><div class="panel-body">
<form method="GET" action="{{ route('admin.fabric-qualities.index') }}" class="row" style="margin-bottom:10px">
<div class="col-sm-3"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Quality name or code"></div>
<div class="col-sm-2"><input name="gsm" value="{{ request('gsm') }}" class="form-control" placeholder="GSM"></div>
<div class="col-sm-2"><input name="width" value="{{ request('width') }}" class="form-control" placeholder="Width"></div>
<div class="col-sm-2"><select name="status" class="form-control"><option value="">All Statuses</option>@foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
<div class="col-sm-1"><button class="btn btn-add">Filter</button></div><div class="col-sm-2"><a href="{{ route('admin.fabric-qualities.create') }}" class="btn btn-add"><i class="fa fa-plus"></i> Add Quality</a></div>
</form>
<div class="table-responsive"><table class="table table-bordered table-striped table-hover"><thead><tr class="info"><th>Quality Name</th><th>Code</th><th>GSM</th><th>Width</th><th>Status</th><th>Actions</th></tr></thead><tbody>
@forelse($qualities as $quality)<tr><td>{{ $quality->quality_name }}</td><td>{{ $quality->quality_code ?: '-' }}</td><td>{{ $quality->gsm ?: '-' }}</td><td>{{ $quality->width ?: '-' }}</td><td>{{ $quality->status }}</td><td><a href="{{ route('admin.fabric-qualities.edit', enc($quality->id)) }}"><i class="fa fa-pencil"></i></a> <form method="POST" action="{{ route($quality->status === 'Active' ? 'admin.fabric-qualities.deactivate' : 'admin.fabric-qualities.activate', enc($quality->id)) }}" style="display:inline">@csrf @method('PATCH')<button class="btn btn-xs {{ $quality->status === 'Active' ? 'btn-warning' : 'btn-success' }}">{{ $quality->status === 'Active' ? 'Deactivate' : 'Activate' }}</button></form></td></tr>
@empty<tr><td colspan="6" class="text-center">No records found.</td></tr>@endforelse
</tbody></table></div><div class="pagination">{{ $qualities->links() }}</div>
</div></div></section></div>
@include('admin.common.footer')</div>@include('admin.common.formfooterscript')
</body></html>
