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
                <div class="header-icon"><i class="fa fa-key"></i></div>
                <div class="header-title">
                    <h1>User Permission Overrides</h1>
                    <small>{{ $user->name }} — individual permission adjustments</small>
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
                                <div class="btn-group">
                                    <a href="{{ route('admin.users.index') }}" class="btn btn-add"><i class="fa fa-list"></i> Users List</a>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="alert alert-info">
                                    <strong>User:</strong> {{ $user->name }} ({{ $user->email }})<br>
                                    <strong>Role(s):</strong> {{ $roles->pluck('name')->join(', ') ?: 'None' }}<br>
                                    Effective access = Role access + Allow − Deny. A Deny override always wins.
                                </div>

                                <div class="row" style="margin-bottom: 15px;">
                                    <div class="col-sm-6">
                                        <label for="permission-filter">Filter Permissions</label>
                                        <div class="input-group">
                                            <span class="input-group-addon"><i class="fa fa-search"></i></span>
                                            <input id="permission-filter" class="form-control" type="search" placeholder="Search modules or actions..." autocomplete="off">
                                        </div>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('admin.users.permissions.update', $user) }}">
                                    @csrf
                                    @method('PUT')

                                    @forelse ($permissions as $resource => $items)
                                        <div class="panel panel-default permission-module">
                                            <div class="panel-heading">
                                                <strong>{{ ucfirst(str_replace('-', ' ', $resource)) }}</strong>
                                                <span class="label label-info pull-right">{{ $items->count() }} actions</span>
                                            </div>
                                            <div class="panel-body">
                                                @foreach ($items as $permission)
                                                    @php($key = $permission->permission_key)
                                                    <div class="row permission-item" data-search="{{ strtolower($resource.' '.$key.' '.$permission->description) }}" style="padding: 7px 0; border-bottom: 1px solid #eeeeee;">
                                                        <div class="col-sm-4">
                                                            <strong>{{ ucfirst(str_replace('-', ' ', $permission->action)) }}</strong>
                                                            <small class="help-block">{{ $key }}</small>
                                                        </div>
                                                        <div class="col-sm-3">
                                                            <select name="permissions[{{ $key }}]" class="form-control input-sm">
                                                                <option value="" @selected(! isset($overrides[$key]))>Inherit role</option>
                                                                <option value="Allow" @selected(($overrides[$key]->effect ?? null) === 'Allow')>Allow for this user</option>
                                                                <option value="Deny" @selected(($overrides[$key]->effect ?? null) === 'Deny')>Deny for this user</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-sm-5">
                                                            <span class="label label-{{ $roleKeys->contains($key) ? 'success' : 'default' }}">Role: {{ $roleKeys->contains($key) ? 'Allowed' : 'Not allowed' }}</span>
                                                            <span class="label label-info">Override: {{ $overrides[$key]->effect ?? 'Inherit' }}</span>
                                                            <span class="label label-{{ $effective->contains($key) ? 'success' : 'danger' }}">Effective: {{ $effective->contains($key) ? 'Allowed' : 'Denied' }}</span>
                                                            @if ($permission->description)
                                                                <small class="help-block">{{ $permission->description }}</small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @empty
                                        <div class="alert alert-warning">No assignable permissions are available for this user.</div>
                                    @endforelse

                                    <div class="reset-button">
                                        <a class="btn btn-warning" href="{{ route('admin.users.index') }}">Cancel</a>
                                        <button class="btn btn-success" type="submit"><i class="fa fa-save"></i> Save User Overrides</button>
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
        });
    </script>
</body>

</html>
