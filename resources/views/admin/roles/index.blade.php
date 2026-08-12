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
                <div class="header-icon"><i class="fa fa-shield"></i></div>
                <div class="header-title">
                    <h1>Roles</h1>
                    <small>Company role and permission management</small>
                </div>
            </section>

            <section class="content">
                {!! display_message('message') !!}

                <div class="row">
                    <div class="col-sm-12">
                        <div class="panel panel-bd lobidrag">
                            <div class="panel-heading">
                                <div class="btn-group"><h4>Roles List</h4></div>
                            </div>
                            <div class="panel-body">
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i>
                                    Only company roles are shown here. The reserved Super Admin role cannot be viewed or assigned from this screen.
                                </div>

                                <div class="row" style="margin-bottom: 10px;">
                                    <div class="col-sm-5">
                                        <div class="input-group">
                                            <span class="input-group-addon"><i class="fa fa-search"></i></span>
                                            <input id="role-filter" class="form-control" type="search" placeholder="Search roles..." aria-label="Search roles" autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="col-sm-7 text-right">
                                        <a class="btn btn-add" href="{{ route('admin.roles.create') }}"><i class="fa fa-plus"></i> Add Role</a>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-hover" id="roles-table">
                                        <thead>
                                            <tr class="info">
                                                <th>Role Name</th>
                                                <th>Login Panel</th>
                                                <th>Status</th>
                                                <th>Permissions</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($roles as $role)
                                                <tr>
                                                    <td>
                                                        <strong>{{ $role->name }}</strong>
                                                        @if ($role->description)
                                                            <small class="help-block">{{ $role->description }}</small>
                                                        @endif
                                                    </td>
                                                    <td><span class="label label-{{ $role->panel === 'Admin' ? 'primary' : 'info' }}">{{ $role->panel }}</span></td>
                                                    <td><span class="label label-{{ $role->status === 'Active' ? 'success' : 'default' }}">{{ $role->status }}</span></td>
                                                    <td>{{ $role->permissions->count() }}</td>
                                                    <td>
                                                        <a class="btn btn-xs btn-info" href="{{ route('admin.roles.edit', $role) }}"><i class="fa fa-pencil"></i> Permissions</a>
                                                        <a class="btn btn-xs btn-primary" href="{{ route('admin.roles.assign', $role) }}"><i class="fa fa-users"></i> Assign Users</a>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="5" class="text-center">No company roles found.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        @include('admin.common.footer')
    </div>

    @include('admin.common.formfooterscript')
    <script>
        jQuery(function ($) {
            $('#role-filter').on('input', function () {
                var query = this.value.toLowerCase();

                $('#roles-table tbody tr').each(function () {
                    $(this).toggle($(this).text().toLowerCase().indexOf(query) !== -1);
                });
            });
        });
    </script>
</body>

</html>
