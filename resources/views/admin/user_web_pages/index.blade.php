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
            <section class="content-header"><div class="header-icon"><i class="fa fa-list"></i></div><div class="header-title"><h1>User Web Pages</h1><small>User Web Pages list</small></div></section>
            <section class="content">
                {!! display_message('message') !!}
                <div class="row"><div class="col-sm-12"><div class="panel panel-bd lobidrag">
                    <div class="panel-heading"><div class="btn-group"><h4>User Web Pages List</h4></div></div>
                    <div class="panel-body">
                        <div class="row" style="margin-bottom:5px">
                            <form action="{{ route('admin.user-web-pages.index') }}" method="GET">
                                <div class="col-sm-4"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search"></div>
                                <div class="col-sm-2"><button class="btn btn-add">Search</button></div>
                            </form>
                            <div class="col-sm-3"><a href="{{ route('admin.user-web-pages.create') }}" class="btn btn-add"><i class="fa fa-plus"></i> Add User Web Pages</a></div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead><tr class="info"><th>User Id</th><th>Page Id</th><th>Status</th><th>Action</th><th>Delete</th></tr></thead>
                                <tbody>
                                    @forelse ($userWebPages as $row)
                                        <tr id="user_web_pages-row-{{ $row->id }}">
                                            <td>{{ $row->user_id }}</td>
                                            <td>{{ $row->page_id }}</td>
                                            <td>{{ $row->status }}</td>
                                            <td><a href="{{ route('admin.user-web-pages.edit', enc($row->id)) }}"><i class="fa fa-pencil"></i></a></td>
                                            <td><button type="button" class="btn btn-danger btn-xs" onclick="deleteRecord('{{ enc($row->id) }}', {{ $row->id }})"><i class="fa fa-trash-o"></i></button></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8" class="text-center">No records found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="pagination">{{ $userWebPages->links() }}</div>
                    </div>
                </div></div></div>
            </section>
        </div>
        @include('admin.common.footer')
    </div>
    @include('admin.common.formfooterscript')
    <script>
        function deleteRecord(id, rowId) {
            if (!confirm('Do you really want to delete this record?')) { return; }
            $.ajax({
                type: 'DELETE',
                url: '{{ url('/admin/user-web-pages') }}/' + encodeURIComponent(id),
                data: { _token: '{{ csrf_token() }}' },
                success: function () { $('#user_web_pages-row-' + rowId).hide(); },
                error: function () { alert('Record delete nahi ho paya. Please try again.'); }
            });
        }
    </script>
</body>
</html>

