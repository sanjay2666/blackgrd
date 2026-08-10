@php($authorization = app(\App\Services\AuthorizationService::class))
@php($navigation = \App\Support\AdminNavigation::visible($authorization))
<aside class="main-sidebar">
    <div class="sidebar">
        <ul class="sidebar-menu">
            @if ($authorization->can('dashboard.view'))
                <li class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <a href="{{ route('admin.dashboard') }}"><i class="fa fa-tachometer"></i><span>Dashboard</span></a>
                </li>
            @endif

            @foreach ($navigation as $group)
                @php($groupOpen = collect($group['items'])->contains(fn (array $item): bool => request()->routeIs($item['active'])) )
                <li class="treeview {{ $groupOpen ? 'active menu-open' : '' }}">
                    <a href="javascript:void(0);"><i class="fa {{ $group['icon'] }}"></i><span>{{ $group['label'] }}</span><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
                    <ul class="treeview-menu" style="{{ $groupOpen ? 'display:block;' : '' }}">
                        @foreach ($group['items'] as $item)
                            <li class="{{ request()->routeIs($item['active']) ? 'active' : '' }}"><a href="{{ route($item['route']) }}">{{ $item['label'] }}</a></li>
                        @endforeach
                    </ul>
                </li>
            @endforeach
        </ul>
    </div>
</aside>
