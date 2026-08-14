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
        if (! $user instanceof User || $route === null || ! Schema::hasTable('all_pages') || ! Schema::hasTable('user_web_pages')) {
            return $this->deny($request);
        }

        $page = AllPage::findForFrontendRoute($route, $request->method());
        if ($page === null || ! UserWebPage::query()->where('user_id', $user->id)->where('page_id', $page->id)->where('status', 'Active')->exists()) {
            return $this->deny($request);
        }

        return $next($request);
    }

    private function deny(Request $request): Response
    {
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['message' => 'आपको इस action की अनुमति नहीं है।'], 403);
        }

        $previous = url()->previous();
        $previousScheme = parse_url($previous, PHP_URL_SCHEME);
        $previousHost = parse_url($previous, PHP_URL_HOST);
        $previousPort = parse_url($previous, PHP_URL_PORT);
        $previousPath = '/'.ltrim((string) parse_url($previous, PHP_URL_PATH), '/');
        $currentPath = '/'.ltrim($request->path(), '/');
        $previousEffectivePort = $previousPort === null ? ($previousScheme === 'https' ? 443 : 80) : (int) $previousPort;
        $isSafePreviousUrl = $previousScheme === $request->getScheme()
            && $previousHost === $request->getHost()
            && $previousEffectivePort === $request->getPort()
            && $previousPath !== $currentPath;

        $redirect = $isSafePreviousUrl ? redirect()->to($previous) : redirect()->route('home');

        return $redirect
            ->with('message', 'आपको इस पेज को एक्सेस करने की अनुमति नहीं है।')
            ->with('messageClass', 'errorClass');
    }
}
