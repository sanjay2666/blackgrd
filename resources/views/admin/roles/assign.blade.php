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
                <div class="header-icon"><i class="fa fa-users"></i></div>
                <div class="header-title">
                    <h1>Assign Role</h1>
                    <small>Assign {{ $role->name }} to an eligible company user</small>
                </div>
            </section>

            <section class="content">
                {!! display_message('message') !!}

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
                                    <a href="{{ route('admin.roles.index') }}" class="btn btn-add"><i class="fa fa-list"></i> Roles List</a>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="alert alert-info">
                                    <strong>{{ $role->name }}</strong>
                                    <span class="label label-{{ $role->panel === 'Admin' ? 'primary' : 'info' }}">{{ $role->panel }}</span>
                                    <span class="label label-{{ $role->status === 'Active' ? 'success' : 'default' }}">{{ $role->status }}</span>
                                </div>

                                <form method="POST" action="{{ route('admin.roles.assign.store', $role) }}">
                                    @csrf
                                    <div class="row">
                                        <div class="col-sm-8">
                                            <div class="form-group">
                                                <label for="role-user">Eligible Company User <span class="required">*</span></label>
                                                <select id="role-user" class="form-control" name="user_id" required>
                                                    <option value="">Select eligible company user</option>
                                                    @foreach ($users as $user)
                                                        <option value="{{ $user->id }}" @selected((string) old('user_id') === (string) $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    @if ($users->isEmpty())
                                        <div class="alert alert-warning">No eligible company users are currently available for this role.</div>
                                    @endif

                                    <div class="reset-button">
                                        <a href="{{ route('admin.roles.index') }}" class="btn btn-warning">Cancel</a>
                                        <button class="btn btn-success" type="submit" @disabled($users->isEmpty())><i class="fa fa-check"></i> Assign Role</button>
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
</body>

</html>
