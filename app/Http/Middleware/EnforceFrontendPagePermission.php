<?php

namespace App\Http\Middleware;

use App\Models\AllPage;
use App\Models\User;
use App\Models\UserWebPage;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

final class EnforceFrontendPagePermission
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth('admin')->check()) {
            return $next($request);
        }

        $user = auth('web')->user();
        $route = $request->route();
        abort_unless($user instanceof User && $route !== null, 403);
        abort_unless(Schema::hasTable('all_pages') && Schema::hasTable('user_web_pages'), 403);

        $page = AllPage::findForFrontendRoute($route, $request->method());
        abort_unless($page !== null, 403);
        abort_unless(UserWebPage::query()->where('user_id', $user->id)->where('page_id', $page->id)->where('status', 'Active')->exists(), 403);

        return $next($request);
    }
}
