<!DOCTYPE html>
<html><head>@include('admin.common.head')</head><body class="hold-transition sidebar-mini"><div class="wrapper">@include('admin.common.header')@include('admin.common.sidebar')<div class="content-wrapper"><section class="content">
    <h3>Department Access: {{ $user->name }}</h3>
    <p class="text-muted">RBAC answers <strong>what</strong>. Department Access answers <strong>where</strong>. An empty selection denies Department-owned operations.</p>
    @if ($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    <form method="POST" action="{{ route('admin.users.department-access.update', $user) }}">@csrf @method('PUT')
        <div class="panel panel-default"><div class="panel-heading">Allowed active Departments</div><div class="panel-body">
            @forelse($departments as $department)<label class="checkbox"><input type="checkbox" name="department_ids[]" value="{{ $department->id }}" @checked(in_array($department->id, $assigned, true))> {{ $department->department_name }} @if($department->factory)<span class="text-muted">({{ $department->factory->name }})</span>@endif @if((int) $home === (int) $department->id)<span class="label label-info">Home</span>@endif</label>@empty<p class="text-muted">No active Departments are available.</p>@endforelse
        </div></div>
        <button class="btn btn-primary" type="submit">Save Department Access</button> <a class="btn btn-default" href="{{ route('admin.users.index') }}">Back</a>
    </form>
</section></div></div>@include('admin.common.footer')</body></html>
