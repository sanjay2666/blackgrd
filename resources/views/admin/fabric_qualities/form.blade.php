<!DOCTYPE html>
<html lang="en">
<head>@include('admin.common.head')</head>
<body class="hold-transition sidebar-mini"><div class="wrapper">@include('admin.common.header') @include('admin.common.sidebar')
<div class="content-wrapper"><section class="content-header"><div class="header-icon"><i class="fa fa-th-list"></i></div><div class="header-title"><h1>{{ $quality->exists ? 'Edit' : 'Add' }} Fabric Quality</h1><small>Fabric specification master</small></div></section>
<section class="content"><div class="panel panel-bd lobidrag"><div class="panel-heading"><a href="{{ route('admin.fabric-qualities.index') }}" class="btn btn-add"><i class="fa fa-list"></i> Quality List</a></div><div class="panel-body">
{!! display_message('message') !!}@if($errors->any())<div class="alert alert-danger">Please fix the errors below.</div>@endif
<form method="POST" action="{{ $quality->exists ? route('admin.fabric-qualities.update', enc($quality->id)) : route('admin.fabric-qualities.store') }}">@csrf @if($quality->exists) @method('PUT') @endif
<div class="row"><div class="col-sm-6"><div class="form-group"><label>Quality Name <span class="required">*</span></label><input required name="quality_name" value="{{ old('quality_name', $quality->quality_name) }}" class="form-control">@error('quality_name')<span class="text-danger small">{{ $message }}</span>@enderror</div></div>
<div class="col-sm-3"><div class="form-group"><label>Quality Code</label><input name="quality_code" value="{{ old('quality_code', $quality->quality_code) }}" class="form-control">@error('quality_code')<span class="text-danger small">{{ $message }}</span>@enderror</div></div>
<div class="col-sm-3"><div class="form-group"><label>Status</label><select name="status" class="form-control">@include('admin.common.status-options', ['selectedStatus' => old('status', $quality->exists ? $quality->getRawOriginal('status') : 'Active')])</select></div></div>
<div class="col-sm-3"><div class="form-group"><label>GSM</label><input name="gsm" value="{{ old('gsm', $quality->gsm) }}" class="form-control" placeholder="Existing notation accepted"></div></div>
<div class="col-sm-3"><div class="form-group"><label>Width</label><input name="width" value="{{ old('width', $quality->width) }}" class="form-control" placeholder="Existing notation accepted"></div></div>
<div class="col-sm-3"><div class="form-group"><label>Display Order</label><input type="number" min="0" name="display_order" value="{{ old('display_order', $quality->display_order) }}" class="form-control"></div></div>
<div class="col-sm-12"><div class="form-group"><label>Description</label><textarea name="description" rows="3" class="form-control">{{ old('description', $quality->description) }}</textarea></div></div></div>
<div class="reset-button"><a href="{{ route('admin.fabric-qualities.index') }}" class="btn btn-warning">Cancel</a> <button class="btn btn-success">Save</button></div>
</form></div></div></section></div>@include('admin.common.footer')</div>@include('admin.common.formfooterscript')</body></html>
