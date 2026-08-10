<h1>User Permission Management</h1>
<p>User: {{ $user->name }} ({{ $user->email }})</p>
<p>Assigned role(s): {{ $roles->pluck('name')->join(', ') ?: 'None' }}</p>
<form method="POST" action="{{ route('admin.users.permissions.update', $user) }}">
    @csrf
    @method('PUT')
    @foreach ($permissions as $resource => $items)
        <fieldset>
            <legend>{{ ucfirst(str_replace('-', ' ', $resource)) }}</legend>
            @foreach ($items as $permission)
                @php($key = $permission->permission_key)
                <label>{{ ucfirst(str_replace('-', ' ', $permission->action)) }}
                    <select name="permissions[{{ $key }}]">
                        <option value="" @selected(! isset($overrides[$key]))>Inherit role</option>
                        <option value="Allow" @selected(($overrides[$key]->effect ?? null) === 'Allow')>Allow for this user</option>
                        <option value="Deny" @selected(($overrides[$key]->effect ?? null) === 'Deny')>Deny for this user</option>
                    </select>
                    — role: {{ $roleKeys->contains($key) ? 'yes' : 'no' }}, final: {{ $effective->contains($key) ? 'allowed' : 'denied' }}
                </label><br>
            @endforeach
        </fieldset>
    @endforeach
    <button type="submit">Save permissions</button>
</form>
