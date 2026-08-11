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
            <section class="content-header"><div class="header-icon"><i class="fa fa-list"></i></div><div class="header-title"><h1>Edit Warehouse Compartments</h1><small>Warehouse Compartments</small></div></section>
            <section class="content"><div class="row"><div class="col-sm-12"><div class="panel panel-bd lobidrag">
                <div class="panel-heading"><a href="{{ route('admin.ware-house-compartments.index') }}" class="btn btn-add"><i class="fa fa-list"></i> Warehouse Compartments List</a></div>
                <div class="panel-body">
                    {!! display_message('message') !!}
                    <form method="POST" action="{{ route('admin.ware-house-compartments.update', enc($wareHouseCompartment->id)) }}">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-sm-4"><div class="form-group"><label>Compartment Name <span class="required">*</span></label><input type="text" name="compartment_name" value="{{ old('compartment_name', $wareHouseCompartment->compartment_name) }}" class="form-control" required></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Warehouse <span class="required">*</span></label><select name="warehouse_id" class="form-control" required><option value="">Select Warehouse</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected(old('warehouse_id', $wareHouseCompartment->warehouse_id) == $warehouse->id)>{{ $warehouse->warehouse_name }}@if($warehouse->factory) — {{ $warehouse->factory->name }}@endif</option>@endforeach</select></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Employee Id</label><input type="number" name="ind_emp_id" value="{{ old('ind_emp_id', $wareHouseCompartment->ind_emp_id) }}" class="form-control" step="any"></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Status</label><select name="status" class="form-control">@include('admin.common.status-options', ['selectedStatus' => $wareHouseCompartment->status])</select></div></div>
                        </div>
                        <div class="reset-button"><a href="{{ route('admin.ware-house-compartments.index') }}" class="btn btn-warning">Cancel</a> <button type="submit" class="btn btn-success">Save</button></div>
                    </form>
                </div>
            </div></div></div></section>
        </div>
        @include('admin.common.footer')
    </div>
    @include('admin.common.formfooterscript')
</body>
</html>
