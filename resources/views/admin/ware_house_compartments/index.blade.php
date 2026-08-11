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
            <section class="content-header"><div class="header-icon"><i class="fa fa-list"></i></div><div class="header-title"><h1>Warehouse Compartments</h1><small>Warehouse Compartments list</small></div></section>
            <section class="content">
                {!! display_message('message') !!}
                <div class="row"><div class="col-sm-12"><div class="panel panel-bd lobidrag">
                    <div class="panel-heading"><div class="btn-group"><h4>Warehouse Compartments List</h4></div></div>
                    <div class="panel-body">
                        <div class="row" style="margin-bottom:5px">
                            <form action="{{ route('admin.ware-house-compartments.index') }}" method="GET">
                                <div class="col-sm-3"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search name"></div>
                                <div class="col-sm-3"><select name="warehouse_id" class="form-control"><option value="">All Warehouses</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected((string) request('warehouse_id') === (string) $warehouse->id)>{{ $warehouse->warehouse_name }}</option>@endforeach</select></div>
                                <div class="col-sm-2"><select name="status" class="form-control"><option value="">All Statuses</option>@foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
                                <div class="col-sm-2"><button class="btn btn-add">Search</button></div>
                            </form>
                            <div class="col-sm-2"><a href="{{ route('admin.ware-house-compartments.create') }}" class="btn btn-add"><i class="fa fa-plus"></i> Add Compartment</a></div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead><tr class="info"><th>Compartment / Bin</th><th>Warehouse</th><th>Factory / Branch</th><th>Status</th><th>Actions</th></tr></thead>
                                <tbody>
                                    @forelse ($wareHouseCompartments as $row)
                                        <tr id="ware_house_compartments-row-{{ $row->id }}">
                                            <td>{{ $row->compartment_name }}</td>
                                            <td>{{ $row->warehouse?->warehouse_name ?? '—' }}</td>
                                            <td>{{ $row->warehouse?->factory?->name ?? 'Company / central' }}</td>
                                            <td>{{ $row->status }}</td>
                                            <td><a class="btn btn-xs btn-default" href="{{ route('admin.ware-house-compartments.edit', enc($row->id)) }}">Edit</a>@if($row->status === 'Active')<form class="inline-form" method="POST" action="{{ route('admin.ware-house-compartments.deactivate', enc($row->id)) }}">@csrf @method('PATCH')<button class="btn btn-xs btn-warning">Deactivate</button></form>@else<form class="inline-form" method="POST" action="{{ route('admin.ware-house-compartments.activate', enc($row->id)) }}">@csrf @method('PATCH')<button class="btn btn-xs btn-success">Activate</button></form>@endif</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8" class="text-center">No records found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="pagination">{{ $wareHouseCompartments->links() }}</div>
                    </div>
                </div></div></div>
            </section>
        </div>
        @include('admin.common.footer')
    </div>
    @include('admin.common.formfooterscript')
</body>
</html>

