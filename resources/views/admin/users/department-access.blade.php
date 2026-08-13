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
                <div class="header-icon"><i class="fa fa-building"></i></div>
                <div class="header-title">
                    <h1>Department Access</h1>
                    <small>{{ $user->name }} — operational department scope</small>
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
                                    RBAC controls <strong>what</strong> this user may do; Department Access controls <strong>where</strong>. An empty selection denies department-owned operations.
                                </div>

                                <form method="POST" action="{{ route('admin.users.department-access.update', $user) }}">
                                    @csrf
                                    @method('PUT')

                                    <div class="panel panel-default">
                                        <div class="panel-heading"><strong>Allowed Active Departments</strong></div>
                                        <div class="panel-body">
                                            <div class="checkbox checkbox-success">
                                                <input id="department-access-select-all" type="checkbox">
                                                <label for="department-access-select-all">Select All Active Departments</label>
                                            </div>
                                            <div class="row">
                                                @forelse ($departments as $department)
                                                    <div class="col-sm-6 col-md-4">
                                                        <div class="checkbox checkbox-success">
                                                            <input id="department-access-{{ $department->id }}" class="department-access-option" type="checkbox" name="department_ids[]" value="{{ $department->id }}" @checked(in_array($department->id, old('department_ids', $assigned), true))>
                                                            <label for="department-access-{{ $department->id }}">
                                                                {{ $department->department_name }}
                                                                @if ($department->factory)
                                                                    <span class="text-muted">({{ $department->factory->name }})</span>
                                                                @endif
                                                                @if ((int) $home === (int) $department->id)
                                                                    <span class="label label-info">Home</span>
                                                                @endif
                                                            </label>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div class="col-sm-12"><p class="text-muted">No active Departments are available.</p></div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>

                                    <div class="reset-button">
                                        <a class="btn btn-warning" href="{{ route('admin.users.index') }}">Cancel</a>
                                        <button class="btn btn-success" type="submit"><i class="fa fa-save"></i> Save Department Access</button>
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
        document.addEventListener('DOMContentLoaded', function () {
            var selectAll = document.getElementById('department-access-select-all');
            var options = Array.prototype.slice.call(document.querySelectorAll('.department-access-option'));
            if (! selectAll || options.length === 0) {
                return;
            }
            var syncSelectAll = function () {
                selectAll.checked = options.every(function (option) { return option.checked; });
                selectAll.indeterminate = ! selectAll.checked && options.some(function (option) { return option.checked; });
            };
            selectAll.addEventListener('change', function () {
                options.forEach(function (option) { option.checked = selectAll.checked; });
                syncSelectAll();
            });
            options.forEach(function (option) { option.addEventListener('change', syncSelectAll); });
            syncSelectAll();
        });
    </script>
</body>

</html>
