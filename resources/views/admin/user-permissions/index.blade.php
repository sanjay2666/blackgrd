<!DOCTYPE html><html><head>@include('admin.common.head')</head><body class="hold-transition sidebar-mini"><div class="wrapper">@include('admin.common.header')@include('admin.common.sidebar')<div class="content-wrapper"><section class="content"><h3>User permission overrides</h3><p>User: {{ $user->name }} ({{ $user->email }})</p><p>Role(s): <strong>{{ $roles->pluck('name')->join(', ') ?: 'None' }}</strong>. Formula: role access + Allow − Deny; Deny wins.</p><input id="permission-filter" class="form-control" type="search" placeholder="Filter modules or actions...">
<form method="POST" action="{{ route('admin.users.permissions.update', $user) }}">
    @csrf
    @method('PUT')
    @foreach ($permissions as $resource => $items)
        <fieldset class="permission-module">
            <legend>{{ ucfirst(str_replace('-', ' ', $resource)) }}</legend>
            @foreach ($items as $permission)
                @php($key = $permission->permission_key)
                <div class="permission-item" data-search="{{ strtolower($key.' '.$permission->description) }}"><label>{{ ucfirst(str_replace('-', ' ', $permission->action)) }} <small>({{ $key }})</small>
                    <select name="permissions[{{ $key }}]" class="form-control" style="display:inline-block;width:auto;margin-left:10px">
                        <option value="" @selected(! isset($overrides[$key]))>Inherit role</option>
                        <option value="Allow" @selected(($overrides[$key]->effect ?? null) === 'Allow')>Allow for this user</option>
                        <option value="Deny" @selected(($overrides[$key]->effect ?? null) === 'Deny')>Deny for this user</option>
                    </select> <span class="label label-{{ $roleKeys->contains($key) ? 'success' : 'default' }}">Role: {{ $roleKeys->contains($key) ? 'Allowed' : 'Not allowed' }}</span> <span class="label label-info">Override: {{ $overrides[$key]->effect ?? 'Inherit' }}</span> <span class="label label-{{ $effective->contains($key) ? 'success' : 'danger' }}">Effective: {{ $effective->contains($key) ? 'Allowed' : 'Denied' }}</span>
                </label></div>
            @endforeach
        </fieldset>
    @endforeach
    <button class="btn btn-primary" type="submit">Save user overrides</button>
</form>
<script>jQuery(function($){$('#permission-filter').on('input',function(){var q=this.value.toLowerCase();$('.permission-item').each(function(){$(this).toggle(!q||$(this).data('search').indexOf(q)!==-1);});});});</script></section></div></div>@include('admin.common.footer')</body></html>
