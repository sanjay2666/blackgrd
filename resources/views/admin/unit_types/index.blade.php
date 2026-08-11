<!DOCTYPE html>
<html lang="en">
<head>@include('admin.common.head')</head>
<body class="hold-transition sidebar-mini">
<div id="preloader"><div id="status"></div></div>
<div class="wrapper">
    @include('admin.common.header')
    @include('admin.common.sidebar')
    <div class="content-wrapper">
        <section class="content-header"><div class="header-icon"><i class="fa fa-balance-scale"></i></div><div class="header-title"><h1>Unit Master</h1><small>Company-global measurement identities</small></div></section>
        <section class="content">
            {!! display_message('message') !!}
            <div class="row"><div class="col-sm-12"><div class="panel panel-bd lobidrag">
                <div class="panel-heading"><h4>Units <a href="{{ route('admin.unit-types.create') }}" class="btn btn-add pull-right"><i class="fa fa-plus"></i> Add Unit</a></h4></div>
                <div class="panel-body">
                    <form action="{{ route('admin.unit-types.index') }}" method="GET" class="form-inline" style="margin-bottom:15px">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Name or code">
                        <select name="status" class="form-control"><option value="">All statuses</option><option value="Active" @selected(request('status') === 'Active')>Active</option><option value="Inactive" @selected(request('status') === 'Inactive')>Inactive</option></select>
                        <button class="btn btn-primary">Search</button>
                        <a href="{{ route('admin.unit-types.index') }}" class="btn btn-default">Reset</a>
                    </form>
                    <div class="table-responsive"><table class="table table-bordered table-striped table-hover">
                        <thead><tr class="info"><th>Unit Name</th><th>Short Code</th><th>Precision</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                        @forelse ($unitTypes as $row)
                            <tr id="unit-row-{{ $row->unit_type_id }}"><td>{{ $row->unit_type_name }}</td><td>{{ $row->unit_code ?: '—' }}</td><td>{{ $row->decimal_places === null ? '—' : $row->decimal_places }}</td><td><span class="label label-{{ $row->status === 'Active' ? 'success' : 'default' }}">{{ $row->status }}</span></td><td><a class="btn btn-xs btn-default" href="{{ route('admin.unit-types.edit', enc($row->unit_type_id)) }}"><i class="fa fa-pencil"></i> Edit</a></td></tr>
                        @empty
                            <tr><td colspan="5" class="text-center">No units found.</td></tr>
                        @endforelse
                        </tbody>
                    </table></div>
                    <div class="pagination">{{ $unitTypes->links() }}</div>
                </div>
            </div></div></div>
        </section>
    </div>
    @include('admin.common.footer')
</div>
@include('admin.common.formfooterscript')
</body>
</html>
