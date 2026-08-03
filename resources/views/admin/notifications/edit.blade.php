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
            <section class="content-header"><div class="header-icon"><i class="fa fa-list"></i></div><div class="header-title"><h1>Edit Notifications</h1><small>Notifications</small></div></section>
            <section class="content"><div class="row"><div class="col-sm-12"><div class="panel panel-bd lobidrag">
                <div class="panel-heading"><a href="{{ route('admin.notifications.index') }}" class="btn btn-add"><i class="fa fa-list"></i> Notifications List</a></div>
                <div class="panel-body">
                    {!! display_message('message') !!}
                    <form method="POST" action="{{ route('admin.notifications.update', enc($notification->id)) }}">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-sm-4"><div class="form-group"><label>Process Type Id</label><input type="number" name="process_type_id" value="{{ old('process_type_id', $notification->process_type_id) }}" class="form-control" step="any"></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>User Id <span class="required">*</span></label><input type="number" name="user_id" value="{{ old('user_id', $notification->user_id) }}" class="form-control" step="any" required></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Employee Id</label><input type="number" name="emp_id" value="{{ old('emp_id', $notification->emp_id) }}" class="form-control" step="any"></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Model Name <span class="required">*</span></label><input type="text" name="model_name" value="{{ old('model_name', $notification->model_name) }}" class="form-control" required></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Ref Id</label><input type="number" name="ref_id" value="{{ old('ref_id', $notification->ref_id) }}" class="form-control" step="any"></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Ref Table</label><input type="text" name="ref_table" value="{{ old('ref_table', $notification->ref_table) }}" class="form-control"></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Notification Type</label><input type="text" name="notification_type" value="{{ old('notification_type', $notification->notification_type) }}" class="form-control"></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Title</label><input type="text" name="title" value="{{ old('title', $notification->title) }}" class="form-control"></div></div>
                            <div class="col-sm-6"><div class="form-group"><label>Page Link <span class="required">*</span></label><textarea name="page_link" class="form-control" rows="3" required>{{ old('page_link', $notification->page_link) }}</textarea></div></div>
                            <div class="col-sm-6"><div class="form-group"><label>Message <span class="required">*</span></label><textarea name="message" class="form-control" rows="3" required>{{ old('message', $notification->message) }}</textarea></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Page Name <span class="required">*</span></label><input type="text" name="page_name" value="{{ old('page_name', $notification->page_name) }}" class="form-control" required></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>IP Address <span class="required">*</span></label><input type="text" name="ip_address" value="{{ old('ip_address', $notification->ip_address) }}" class="form-control" required></div></div>
                            <div class="col-sm-6"><div class="form-group"><label>Server Details <span class="required">*</span></label><textarea name="server_details" class="form-control" rows="3" required>{{ old('server_details', $notification->server_details) }}</textarea></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>&nbsp;</label><div class="checkbox"><label><input type="checkbox" name="is_read" value="1" @checked(old('is_read', $notification->is_read))> Is Read</label></div></div></div>
                            <div class="col-sm-4"><div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="Active" @selected(old('status', $) === 'Active')>Active</option><option value="Inactive" @selected(old('status', $) === 'Inactive')>Inactive</option></select></div></div>
                        </div>
                        <div class="reset-button"><a href="{{ route('admin.notifications.index') }}" class="btn btn-warning">Cancel</a> <button type="submit" class="btn btn-success">Save</button></div>
                    </form>
                </div>
            </div></div></div></section>
        </div>
        @include('admin.common.footer')
    </div>
    @include('admin.common.formfooterscript')
</body>
</html>
