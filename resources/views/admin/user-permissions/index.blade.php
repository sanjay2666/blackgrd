<!DOCTYPE html>
<html lang="en">

<head>
    @include('admin.common.head')
    <style>
        .permission-module { margin-bottom: 12px; }
        .permission-module .panel-heading { min-height: 34px; padding: 8px 12px; }
        .permission-grid { margin: 0 -5px; }
        .permission-item { padding: 4px 5px; }
        .permission-choice { display: flex; align-items: center; margin: 0; min-height: 24px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; cursor: pointer; }
        .permission-choice .permission-checkbox { position: static; display: inline-block; visibility: visible; width: 14px; height: 14px; margin: 0 7px 0 0; opacity: 1; -webkit-appearance: checkbox; appearance: checkbox; flex: 0 0 auto; }
    </style>
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
                    <h1>Frontend User Permissions</h1>
                    <small>{{ $user->name }} — page, action and AJAX access</small>
                </div>
            </section>

            <section class="content">
                @if (session('message'))
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <i class="glyphicon glyphicon-ok"></i> {{ session('message') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
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
                                    Checked entries are stored in <code>user_web_pages</code>.
                                    Unchecked entries are disabled for this user. Department Access is separate and unchanged.
                                </div>

                                <div class="row" style="margin-bottom: 15px;">
                                    <div class="col-sm-6">
                                        <label for="permission-filter">Filter Frontend Pages, Actions or AJAX</label>
                                        <div class="input-group">
                                            <span class="input-group-addon"><i class="fa fa-search"></i></span>
                                            <input id="permission-filter" class="form-control" type="search" placeholder="Search modules, routes or AJAX actions..." autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="col-sm-6" style="padding-top: 25px;">
                                        <label class="checkbox-inline">
                                            <input id="select-all-permissions" type="checkbox"> <strong>Select All Frontend Permissions</strong>
                                        </label>
                                        <span id="selected-permission-count" class="text-muted" style="margin-left: 10px;"></span>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('admin.users.permissions.update', $user) }}">
                                    @csrf
                                    @method('PUT')

                                    @forelse ($pages->groupBy('display_module') as $module => $items)
                                        <div class="panel panel-default permission-module">
                                            <div class="panel-heading">
                                                <strong>{{ $module }}</strong>
                                                <span class="label label-info pull-right">{{ $items->count() }} pages / actions</span>
                                                <label class="checkbox-inline pull-right" style="margin: -4px 12px 0 0;">
                                                    <input class="select-module-permissions" type="checkbox"> Select All
                                                </label>
                                            </div>
                                            <div class="row permission-grid">
                                                @foreach ($items as $page)
                                                    <div class="col-md-3 col-sm-4 col-xs-12 permission-item" data-search="{{ strtolower($module.' '.$page->page_title.' '.$page->page_name.' '.$page->route_label) }}">
                                                        <label class="permission-choice" title="{{ $page->page_title }}">
                                                            <input class="permission-checkbox" type="checkbox" name="page_ids[]" value="{{ $page->id }}" @checked(in_array($page->id, $assignedPageIds, true))>
                                                            <span>{{ $page->page_title }}</span>
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @empty
                                        <div class="alert alert-warning">No authenticated Frontend page, action or AJAX route is available.</div>
                                    @endforelse

                                    <div class="reset-button">
                                        <a class="btn btn-warning" href="{{ route('admin.users.index') }}">Cancel</a>
                                        <button class="btn btn-success" type="submit"><i class="fa fa-save"></i> Save Frontend Permissions</button>
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

            function updateSelectionState() {
                var checkboxes = $('.permission-checkbox');
                var selected = checkboxes.filter(':checked').length;

                $('#selected-permission-count').text(selected + ' of ' + checkboxes.length + ' selected');
                $('#select-all-permissions').prop('checked', checkboxes.length > 0 && selected === checkboxes.length);
                $('.permission-module').each(function () {
                    var moduleCheckboxes = $(this).find('.permission-checkbox');
                    $(this).find('.select-module-permissions').prop('checked', moduleCheckboxes.length > 0 && moduleCheckboxes.filter(':checked').length === moduleCheckboxes.length);
                });
            }

            $('#select-all-permissions').on('change', function () {
                $('.permission-checkbox').prop('checked', this.checked);
                updateSelectionState();
            });

            $('.select-module-permissions').on('change', function () {
                $(this).closest('.permission-module').find('.permission-checkbox').prop('checked', this.checked);
                updateSelectionState();
            });

            $('.permission-checkbox').on('change', function () {
                updateSelectionState();
            });
            updateSelectionState();
        });
    </script>
</body>

</html>
