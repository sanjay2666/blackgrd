<div class="container-fluid">
    <h1>Audit Log</h1>
    <form method="GET" class="form-inline" action="{{ route('admin.audit-logs.index') }}">
        <input class="form-control" type="date" name="date_from" value="{{ request('date_from') }}">
        <input class="form-control" type="date" name="date_to" value="{{ request('date_to') }}">
        <input class="form-control" name="actor_type" placeholder="Admin/User/System" value="{{ request('actor_type') }}">
        <input class="form-control" name="actor_id" placeholder="Actor ID" value="{{ request('actor_id') }}">
        <input class="form-control" name="module" placeholder="Module" value="{{ request('module') }}">
        <input class="form-control" name="action" placeholder="Action" value="{{ request('action') }}">
        <input class="form-control" name="entity" placeholder="Entity" value="{{ request('entity') }}">
        <input class="form-control" name="record_id" placeholder="Record ID" value="{{ request('record_id') }}">
        <button class="btn btn-primary" type="submit">Filter</button>
    </form>
    <table class="table table-striped table-condensed">
        <thead><tr><th>Time</th><th>Actor</th><th>Module/Action</th><th>Target</th><th>Description</th><th></th></tr></thead>
        <tbody>
        @forelse ($logs as $log)
            <tr>
                <td>{{ optional($log->created_at)->format('Y-m-d H:i:s') }}</td>
                <td>{{ $log->actor_type }} #{{ $log->actor_id ?? 'system' }} ({{ $log->guard }})</td>
                <td>{{ $log->module }} / {{ $log->action }}</td>
                <td>{{ $log->auditable_type ? class_basename($log->auditable_type).' #'.$log->auditable_id : '—' }}</td>
                <td>{{ $log->description }}</td>
                <td><a class="btn btn-default btn-xs" href="{{ route('admin.audit-logs.show', $log) }}">View Details</a></td>
            </tr>
        @empty
            <tr><td colspan="6">No audit entries found.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $logs->links() }}
</div>
