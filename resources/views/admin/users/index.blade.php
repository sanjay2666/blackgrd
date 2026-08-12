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
            <section class="content-header">
                <div class="header-icon"><i class="fa fa-user"></i></div>
                <div class="header-title">
                    <h1>Frontend Users</h1>
                    <small>Manage Frontend user accounts and access</small>
                </div>
            </section>

            <section class="content">
                {!! display_message('message') !!}
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                <div class="row">
                    <div class="col-sm-12">
                        <div class="panel panel-bd lobidrag">
                            <div class="panel-heading">
                                <div class="btn-group"><h4>Frontend Users List</h4></div>
                            </div>
                            <div class="panel-body">
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i>
                                    This screen manages Frontend User accounts only. Admin and Super Admin identities remain separate.
                                </div>

                                <form method="GET" action="{{ route('admin.users.index') }}">
                                    <div class="row" style="margin-bottom: 10px;">
                                        <div class="col-sm-3">
                                            <input class="form-control" name="search" value="{{ request('search') }}" placeholder="Name, email or mobile">
                                        </div>
                                        <div class="col-sm-2">
                                            <select class="form-control" name="status">
                                                <option value="">All statuses</option>
                                                <option value="Active" @selected(request('status') === 'Active')>Active</option>
                                                <option value="Inactive" @selected(request('status') === 'Inactive')>Inactive</option>
                                            </select>
                                        </div>
                                        <div class="col-sm-3">
                                            <select class="form-control" name="role_id">
                                                <option value="">All frontend roles</option>
                                                @foreach ($roles as $role)
                                                    <option value="{{ $role->id }}" @selected((string) request('role_id') === (string) $role->id)>{{ $role->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-sm-2">
                                            <button class="btn btn-add" type="submit"><i class="fa fa-search"></i> Filter</button>
                                            <a class="btn btn-default" href="{{ route('admin.users.index') }}">Reset</a>
                                        </div>
                                        <div class="col-sm-2 text-right">
                                            <a class="btn btn-add" href="{{ route('admin.users.create') }}"><i class="fa fa-plus"></i> Add User</a>
                                        </div>
                                    </div>
                                </form>

                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-hover table-condensed">
                                        <thead>
                                            <tr class="info">
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Mobile</th>
                                                <th>Role(s)</th>
                                                <th>Home</th>
                                                <th>Departments</th>
                                                <th>Status</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($users as $user)
                                                <tr>
                                                    <td>{{ $user->name }}</td>
                                                    <td>{{ $user->email }}</td>
                                                    <td>{{ $user->individual?->phone ?? '—' }}</td>
                                                    <td>{{ $user->roleAssignments->where('status', 'Active')->pluck('role.name')->filter()->join(', ') ?: 'None' }}</td>
                                                    <td>
                                                        @php($access = $user->organizationAccess->first())
                                                        {{ $access?->department?->department_name ?? $access?->factory?->name ?? $access?->branch?->name ?? 'Company' }}
                                                    </td>
                                                    <td>{{ $user->departmentAccess->where('status', 'Active')->pluck('department.department_name')->filter()->join(', ') ?: 'None' }}</td>
                                                    <td><span class="label label-{{ $user->status === 'Active' ? 'success' : 'default' }}">{{ $user->status }}</span></td>
                                                    <td>{{ optional($user->created_at)->format('d-m-Y') }}</td>
                                                    <td>
                                                        <a class="btn btn-xs btn-default" href="{{ route('admin.users.edit', $user) }}"><i class="fa fa-pencil"></i> Edit</a>
                                                        <a class="btn btn-xs btn-info" href="{{ route('admin.users.permissions.edit', $user) }}"><i class="fa fa-key"></i> Permissions</a>
                                                        <a class="btn btn-xs btn-primary" href="{{ route('admin.users.department-access', $user) }}"><i class="fa fa-building"></i> Departments</a>
                                                        @if ($user->status === 'Active')
                                                            <form method="POST" action="{{ route('admin.users.deactivate', $user) }}" style="display: inline;" onsubmit="return confirm('Deactivate this Frontend User?')">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button class="btn btn-xs btn-warning" type="submit">Deactivate</button>
                                                            </form>
                                                        @else
                                                            <form method="POST" action="{{ route('admin.users.activate', $user) }}" style="display: inline;">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button class="btn btn-xs btn-success" type="submit">Activate</button>
                                                            </form>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="9" class="text-center">No Frontend Users found.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="pagination">{{ $users->links() }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        @include('admin.common.footer')
    </div>

    @include('admin.common.formfooterscript')
</body>

</html>
