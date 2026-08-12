<!DOCTYPE html>
<html lang="en">

<head>
    @include('admin.common.head')
</head>

<body class="hold-transition sidebar-mini">
    <div id="preloader">
        <div id="status"></div>
    </div>

    <div class="wrapper">
        @include('admin.common.header')
        @include('admin.common.sidebar')

        <div class="content-wrapper">
            <section class="content-header">
                <div class="header-icon"><i class="fa fa-shield"></i></div>
                <div class="header-title">
                    <h1>{{ $role->exists ? 'Edit Role' : 'Add Role' }}</h1>
                    <small>Company role and page-action permissions</small>
                </div>
            </section>

            <section class="content">
                {!! display_message('message') !!}

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <strong>Please correct the following:</strong>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row">
                    <div class="col-sm-12">
                        <div class="panel panel-bd lobidrag">
                            <div class="panel-heading">
                                <div class="btn-group">
                                    <a href="{{ route('admin.roles.index') }}" class="btn btn-add">
                                        <i class="fa fa-list"></i> Roles List
                                    </a>
                                </div>
                            </div>

                            <div class="panel-body">
                                <form method="POST" action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}">
                                    @csrf
                                    @if ($role->exists)
                                        @method('PUT')
                                    @endif

                                    <div class="row">
                                        <div class="col-sm-4">
                                            <div class="form-group">
                                                <label for="role-name">Role Name <span class="required">*</span></label>
                                                <input id="role-name" type="text" class="form-control" name="name" value="{{ old('name', $role->name) }}" maxlength="120" required>
                                            </div>
                                        </div>

                                        <div class="col-sm-4">
                                            <div class="form-group">
                                                <label for="role-panel">Login Panel <span class="required">*</span></label>
                                                <select id="role-panel" class="form-control" name="panel" required {{ $role->exists ? 'disabled' : '' }}>
                                                    <option value="Admin" @selected(old('panel', $role->panel ?: 'Admin') === 'Admin')>Admin</option>
                                                    <option value="Frontend" @selected(old('panel', $role->panel) === 'Frontend')>Frontend</option>
                                                </select>
                                                @if ($role->exists)
                                                    <input type="hidden" name="panel" value="{{ $role->panel }}">
                                                @endif
                                            </div>
                                        </div>

                                        <div class="col-sm-4">
                                            <div class="form-group">
                                                <label for="role-status">Status <span class="required">*</span></label>
                                                <select id="role-status" class="form-control" name="status" required>
                                                    <option value="Active" @selected(old('status', $role->status ?: 'Active') === 'Active')>Active</option>
                                                    <option value="Inactive" @selected(old('status', $role->status) === 'Inactive')>Inactive</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="role-description">Description</label>
                                        <textarea id="role-description" class="form-control" name="description" rows="3" placeholder="Describe the responsibilities of this role">{{ old('description', $role->description) }}</textarea>
                                    </div>

                                    <div class="alert alert-info">
                                        <i class="fa fa-info-circle"></i>
                                        Permissions are action-based. Reserved permissions are controlled by the canonical registry and are not available to Company Admins.
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="permission-filter">Filter Permissions</label>
                                                <div class="input-group">
                                                    <span class="input-group-addon"><i class="fa fa-search"></i></span>
                                                    <input id="permission-filter" class="form-control" type="search" placeholder="Search modules or actions..." autocomplete="off">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 text-right">
                                            <label class="hidden-xs">Permission Actions</label>
                                            <div>
                                                <div class="btn-group" role="group" aria-label="Permission selection">
                                                    <button class="btn btn-add" type="button" id="select-visible"><i class="fa fa-check-square-o"></i> Select Visible</button>
                                                    <button class="btn btn-default" type="button" id="clear-visible"><i class="fa fa-square-o"></i> Clear Visible</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    @php($selectedPermissions = old('permissions', $role->permissions->pluck('permission_key')->all()))

                                    @forelse ($permissions as $resource => $items)
                                        <div class="panel panel-default permission-module">
                                            <div class="panel-heading">
                                                <strong>{{ ucfirst(str_replace('-', ' ', $resource)) }}</strong>
                                                <span class="label label-info pull-right">{{ $items->count() }} actions</span>
                                            </div>
                                            <div class="panel-body">
                                                <div class="row">
                                                    @foreach ($items as $permission)
                                                        <div class="col-sm-6 col-md-4 permission-item" data-search="{{ strtolower($resource.' '.$permission->permission_key.' '.$permission->action.' '.$permission->description) }}">
                                                            <div class="checkbox checkbox-success">
                                                                <input id="permission-{{ md5($permission->permission_key) }}" type="checkbox" name="permissions[]" value="{{ $permission->permission_key }}" @checked(in_array($permission->permission_key, $selectedPermissions, true))>
                                                                <label for="permission-{{ md5($permission->permission_key) }}">
                                                                    {{ ucfirst(str_replace('-', ' ', $permission->action)) }}
                                                                    @if ($permission->is_critical)
                                                                        <span class="label label-warning">Critical</span>
                                                                    @endif
                                                                    @if ($permission->description)
                                                                        <small class="help-block">{{ $permission->description }}</small>
                                                                    @endif
                                                                </label>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="alert alert-warning">No assignable permissions are currently available for this panel.</div>
                                    @endforelse

                                    <div class="reset-button">
                                        <a href="{{ route('admin.roles.index') }}" class="btn btn-warning">Cancel</a>
                                        <button class="btn btn-success" type="submit">
                                            <i class="fa fa-save"></i> {{ $role->exists ? 'Update Role' : 'Save Role' }}
                                        </button>
                                    </div>
                                </form>
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
            $('#permission-filter').on('input', function () {
                var query = this.value.toLowerCase();

                $('.permission-module').each(function () {
                    var matched = 0;

                    $(this).find('.permission-item').each(function () {
                        var isMatch = !query || $(this).data('search').indexOf(query) !== -1;
                        $(this).toggle(isMatch);
                        matched += isMatch ? 1 : 0;
                    });

                    $(this).toggle(matched > 0);
                });
            });

            $('#select-visible').on('click', function () {
                $('.permission-item:visible input[type="checkbox"]').prop('checked', true);
            });

            $('#clear-visible').on('click', function () {
                $('.permission-item:visible input[type="checkbox"]').prop('checked', false);
            });
        });
    </script>
</body>

</html>
