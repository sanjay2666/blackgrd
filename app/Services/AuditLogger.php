<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AuditLogger
{
    private const SECRET_KEYS = ['password', 'password_confirmation', 'remember_token', 'token', 'otp', 'csrf', 'cookie', 'session', 'secret', 'api_key', 'authorization'];

    public function record(array $data): ?AuditLog
    {
        if (! Schema::hasTable('audit_logs')) {
            return null;
        }
        $payload = $this->payload($data);

        return AuditLog::create($payload);
    }

    public function recordAfterCommit(array $data): void
    {
        DB::afterCommit(fn () => $this->record($data));
    }

    public function recordMutation(Request $request, string $permission, ?Model $target = null, ?string $event = null): void
    {
        $definition = explode('.', $permission, 2);
        $this->record([
            'module' => $definition[0], 'action' => $definition[1] ?? 'execute',
            'event' => $event ?? 'route_mutation',
            'description' => 'Authorized '.($event ?? 'business action').' executed.',
            'auditable_type' => $target?->getMorphClass(), 'auditable_id' => $target?->getKey(),
            'request' => $request,
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(array $data): array
    {
        $request = $data['request'] ?? request();
        $actor = $this->actor();
        $old = $this->redact($data['old_values'] ?? null);
        $new = $this->redact($data['new_values'] ?? null);
        $changed = $data['changed_fields'] ?? $this->changedFields($old, $new);

        return [
            'actor_type' => $actor['type'], 'actor_id' => $actor['id'], 'guard' => $actor['guard'],
            'module' => (string) ($data['module'] ?? 'system'), 'action' => (string) ($data['action'] ?? 'execute'),
            'auditable_type' => $data['auditable_type'] ?? null, 'auditable_id' => $data['auditable_id'] ?? null,
            'event' => (string) ($data['event'] ?? 'action'), 'description' => $data['description'] ?? null,
            'old_values' => $old, 'new_values' => $new, 'changed_fields' => $changed,
            'route_name' => $request instanceof Request ? $request->route()?->getName() : null,
            'http_method' => $request instanceof Request ? $request->method() : null,
            'ip_address' => $request instanceof Request ? $request->ip() : null,
            'user_agent' => $request instanceof Request ? mb_substr((string) $request->userAgent(), 0, 512) : null,
            'request_id' => $request instanceof Request ? mb_substr((string) ($request->header('X-Request-ID') ?: $request->header('X-Correlation-ID')), 0, 120) : null,
            'created_at' => now(),
        ];
    }

    /** @return array{type:string,id:?int,guard:string} */
    private function actor(): array
    {
        if (app()->runningInConsole() && ! Auth::guard('admin')->check() && ! Auth::guard('web')->check()) {
            return ['type' => 'System', 'id' => null, 'guard' => 'system'];
        }
        $guard = request()->is('admin') || request()->is('admin/*') ? 'admin' : 'web';
        $user = Auth::guard($guard)->user();

        return ['type' => $user instanceof User ? $user->user_type : 'System', 'id' => $user?->getAuthIdentifier(), 'guard' => $guard];
    }

    private function redact(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        $result = [];
        foreach ($value as $key => $item) {
            $normalized = strtolower((string) $key);
            $result[$key] = collect(self::SECRET_KEYS)->contains(fn (string $secret): bool => str_contains($normalized, $secret)) ? '[REDACTED]' : $this->redact($item);
        }

        return $result;
    }

    private function changedFields(?array $old, ?array $new): ?array
    {
        if (! is_array($old) && ! is_array($new)) {
            return null;
        }

        return collect(array_unique(array_merge(array_keys($old ?? []), array_keys($new ?? []))))->filter(fn (string $key): bool => ($old[$key] ?? null) !== ($new[$key] ?? null))->values()->all();
    }
}
