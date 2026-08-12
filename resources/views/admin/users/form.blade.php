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
                    <h1>{{ $user->exists ? 'Edit Frontend User' : 'Add Frontend User' }}</h1>
                    <small>Frontend identity, organization access and roles</small>
                </div>
            </section>

            <section class="content">
                {!! display_message('message') !!}
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
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
                                    <a href="{{ route('admin.users.index') }}" class="btn btn-add"><i class="fa fa-list"></i> Users List</a>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i>
                                    This form manages accounts authenticated by the separate Frontend web guard. Admin identities cannot be created here.
                                </div>

                                <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}">
                                    @csrf
                                    @if ($user->exists)
                                        @method('PUT')
                                    @endif

                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="user-name">Name <span class="required">*</span></label>
                                                <input id="user-name" class="form-control" name="name" value="{{ old('name', $user->name) }}" required>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="user-email">Email <span class="required">*</span></label>
                                                <input id="user-email" class="form-control" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                                            </div>
                                        </div>
                                    </div>

                                    @if (! $user->exists)
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <label for="user-password">Initial Password <span class="required">*</span></label>
                                                    <input id="user-password" class="form-control" type="password" name="password" required>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <label for="user-password-confirmation">Confirm Password <span class="required">*</span></label>
                                                    <input id="user-password-confirmation" class="form-control" type="password" name="password_confirmation" required>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="user-individual">Employee Link</label>
                                                <select id="user-individual" class="form-control" name="individual_id">
                                                    <option value="">None</option>
                                                    @foreach ($individuals as $individual)
                                                        <option value="{{ $individual->id }}" @selected((string) old('individual_id', $user->individual_id) === (string) $individual->id)>
                                                            {{ $individual->name }}{{ $individual->email ? ' — '.$individual->email : '' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="user-status">Status <span class="required">*</span></label>
                                                <select id="user-status" class="form-control" name="status" required>
                                                    <option value="Active" @selected(old('status', $user->exists ? $user->status : 'Active') === 'Active')>Active</option>
                                                    <option value="Inactive" @selected(old('status', $user->status) === 'Inactive')>Inactive</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="panel panel-default">
                                        <div class="panel-heading"><strong>Company Organization Access</strong></div>
                                        <div class="panel-body">
                                            <div class="row">
                                                <div class="col-sm-4">
                                                    <div class="form-group">
                                                        <label for="user-branch">Branch</label>
                                                        <select id="user-branch" class="form-control" name="branch_id">
                                                            <option value="">Any branch</option>
                                                            @foreach ($branches as $branch)
                                                                <option value="{{ $branch->id }}" @selected((string) old('branch_id', $access->branch_id ?? '') === (string) $branch->id)>{{ $branch->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-sm-4">
                                                    <div class="form-group">
                                                        <label for="user-factory">Factory</label>
                                                        <select id="user-factory" class="form-control" name="factory_id">
                                                            <option value="">Any factory</option>
                                                            @foreach ($factories as $factory)
                                                                <option value="{{ $factory->id }}" @selected((string) old('factory_id', $access->factory_id ?? '') === (string) $factory->id)>{{ $factory->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-sm-4">
                                                    <div class="form-group">
                                                        <label for="user-department">Department</label>
                                                        <select id="user-department" class="form-control" name="department_id">
                                                            <option value="">Any department</option>
                                                            @foreach ($departments as $department)
                                                                <option value="{{ $department->id }}" @selected((string) old('department_id', $access->department_id ?? '') === (string) $department->id)>{{ $department->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="panel panel-default">
                                        <div class="panel-heading"><strong>Frontend Roles</strong></div>
                                        <div class="panel-body">
                                            <p class="text-muted">Only active company-scoped Frontend roles are available. Super Admin and Admin roles are excluded server-side.</p>
                                            @forelse ($roles as $role)
                                                <label class="checkbox-inline">
                                                    <input type="checkbox" name="role_ids[]" value="{{ $role->id }}" @checked(in_array($role->id, old('role_ids', $roleIds), true))> {{ $role->name }}
                                                </label>
                                            @empty
                                                <p class="text-warning">No active Frontend role is currently available.</p>
                                            @endforelse
                                        </div>
                                    </div>

                                    <div class="reset-button">
                                        <a class="btn btn-warning" href="{{ route('admin.users.index') }}">Cancel</a>
                                        <button class="btn btn-success" type="submit"><i class="fa fa-save"></i> {{ $user->exists ? 'Update User' : 'Save User' }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($user->exists)
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="panel panel-bd lobidrag">
                                <div class="panel-heading"><div class="btn-group"><h4>Administrative Password Reset</h4></div></div>
                                <div class="panel-body">
                                    <p class="text-muted">The current password is never displayed or logged. Existing sessions are invalidated after reset.</p>
                                    <form method="POST" action="{{ route('admin.users.reset-password', $user) }}">
                                        @csrf
                                        <div class="row">
                                            <div class="col-sm-4"><input class="form-control" type="password" name="password" placeholder="New password" required></div>
                                            <div class="col-sm-4"><input class="form-control" type="password" name="password_confirmation" placeholder="Confirm new password" required></div>
                                            <div class="col-sm-4"><button class="btn btn-warning" type="submit"><i class="fa fa-key"></i> Reset Password</button></div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </section>
        </div>

        @include('admin.common.footer')
    </div>

    @include('admin.common.formfooterscript')
</body>

</html>
