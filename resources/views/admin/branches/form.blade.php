<!DOCTYPE html>
<html lang="en">
<head>@include('admin.common.head')</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    @include('admin.common.header')
    @include('admin.common.sidebar')
    <div class="content-wrapper">
        <section class="content-header">
            <h1>{{ $location->exists ? 'Edit' : 'Add' }} {{ ucfirst($locationType) }}</h1>
        </section>
        <section class="content">
            <div class="panel panel-bd">
                <div class="panel-body">
                    <form method="POST" action="{{ $location->exists ? route('admin.'.($locationType === 'branch' ? 'branches' : 'factories').'.update', $location) : route('admin.'.($locationType === 'branch' ? 'branches' : 'factories').'.store') }}">
                        @csrf
                        @if($location->exists) @method('PUT') @endif
                        <div class="row">
                            <div class="col-sm-4 form-group">
                                <label>Name *</label>
                                <input name="name" value="{{ old('name', $location->name) }}" class="form-control" required>
                            </div>
                            <div class="col-sm-4 form-group">
                                <label>{{ $locationType === 'branch' ? 'Branch' : 'Factory' }} Code *</label>
                                <input name="{{ $locationType }}_code" value="{{ old($locationType.'_code', $location->{$locationType.'_code'}) }}" class="form-control" required>
                            </div>
                            <div class="col-sm-4 form-group">
                                <label>Status *</label>
                                <select name="status" class="form-control">
                                    <option>Active</option>
                                    <option @selected(old('status', $location->status) === 'Inactive')>Inactive</option>
                                </select>
                            </div>
                        </div>
                        @if($locationType === 'branch')
                            <div class="form-group">
                                <label>Kind *</label>
                                <select name="kind" class="form-control">
                                    <option value="head_office" @selected(old('kind', $location->kind) === 'head_office')>Head Office</option>
                                    <option value="commercial" @selected(old('kind', $location->kind) === 'commercial')>Commercial</option>
                                    <option value="other" @selected(old('kind', $location->kind) === 'other')>Other</option>
                                </select>
                            </div>
                        @else
                            <div class="form-group">
                                <label>Parent Branch</label>
                                <select name="branch_id" class="form-control">
                                    <option value="">None</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected(old('branch_id', $location->branch_id) == $branch->id)>{{ $branch->name }} ({{ $branch->branch_code }})</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="row">
                            @foreach(['address' => 'Address', 'city' => 'City', 'state' => 'State', 'pin_code' => 'PIN Code', 'country' => 'Country', 'phone' => 'Phone', 'mobile' => 'Mobile', 'email' => 'Email', 'contact_person' => 'Contact Person', 'gstin' => 'GSTIN'] as $field => $label)
                                <div class="col-sm-4 form-group">
                                    <label>{{ $label }}</label>
                                    <input name="{{ $field }}" value="{{ old($field, $location->{$field}) }}" class="form-control" @if($field === 'email') type="email" @endif>
                                </div>
                            @endforeach
                        </div>
                        <div class="form-group">
                            <label>Remarks</label>
                            <textarea name="remarks" class="form-control">{{ old('remarks', $location->remarks) }}</textarea>
                        </div>
                        <button class="btn btn-success">Save</button>
                        <a href="{{ route($locationType === 'branch' ? 'admin.branches.index' : 'admin.factories.index') }}" class="btn btn-default">Cancel</a>
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
