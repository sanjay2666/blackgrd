<!DOCTYPE html>
<html lang="en"><head>@include('admin.common.head')</head>
<body class="hold-transition sidebar-mini"><div id="preloader"><div id="status"></div></div><div class="wrapper">
@include('admin.common.header') @include('admin.common.sidebar')
<div class="content-wrapper"><section class="content-header"><div class="header-icon"><i class="fa fa-balance-scale"></i></div><div class="header-title"><h1>Edit Unit</h1><small>Identity {{ $unitType->unit_type_id }}</small></div></section>
<section class="content"><div class="row"><div class="col-sm-12"><div class="panel panel-bd lobidrag"><div class="panel-heading"><a href="{{ route('admin.unit-types.index') }}" class="btn btn-add"><i class="fa fa-list"></i> Unit Master</a></div><div class="panel-body">
{!! display_message('message') !!}<form method="POST" action="{{ route('admin.unit-types.update', enc($unitType->unit_type_id)) }}">@csrf @method('PUT') @include('admin.unit_types._form', ['unitType' => $unitType])</form>
</div></div></div></div></section></div>
@include('admin.common.footer')</div>@include('admin.common.formfooterscript')</body></html>
