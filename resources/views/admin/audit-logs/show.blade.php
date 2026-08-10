<div class="container-fluid">
    <h1>Audit Details</h1>
    <dl class="dl-horizontal">
        <dt>Actor</dt><dd>{{ $auditLog->actor_type }} #{{ $auditLog->actor_id ?? 'system' }} via {{ $auditLog->guard }}</dd>
        <dt>Event</dt><dd>{{ $auditLog->module }} / {{ $auditLog->action }} / {{ $auditLog->event }}</dd>
        <dt>Target</dt><dd>{{ $auditLog->auditable_type }} #{{ $auditLog->auditable_id }}</dd>
        <dt>Request</dt><dd>{{ $auditLog->http_method }} {{ $auditLog->route_name }} · {{ $auditLog->ip_address }}</dd>
        <dt>User agent</dt><dd>{{ $auditLog->user_agent }}</dd>
        <dt>Description</dt><dd>{{ $auditLog->description }}</dd>
    </dl>
    <h3>Changed fields</h3><pre>{{ json_encode($auditLog->changed_fields, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    <h3>Before</h3><pre>{{ json_encode($auditLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    <h3>After</h3><pre>{{ json_encode($auditLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    <a class="btn btn-default" href="{{ route('admin.audit-logs.index') }}">Back</a>
</div>
