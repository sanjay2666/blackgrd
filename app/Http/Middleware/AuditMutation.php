<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use App\Support\RoutePermissionRegistry;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

final class AuditMutation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if (! Schema::hasTable('audit_logs') || $response->getStatusCode() >= 400) {
            return $response;
        }
        $permission = RoutePermissionRegistry::permission($request->route());
        if (! $permission || ! $this->isImportant($request, $permission)) {
            return $response;
        }
        app(AuditLogger::class)->recordMutation($request, $permission);

        return $response;
    }

    private function isImportant(Request $request, string $permission): bool
    {
        $action = explode('.', $permission, 2)[1] ?? '';

        return ! in_array($action, ['view', 'print', 'export'], true) || ! in_array($request->method(), ['GET', 'HEAD'], true);
    }
}
