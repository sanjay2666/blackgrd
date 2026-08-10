# Centralized Audit Log

Task 1.8 adds one append-only `audit_logs` table and the reusable
`App\Services\AuditLogger`. It preserves the single-company architecture and
the separate `admin`/`web` guards.

## Actors and context

Rows record `Admin`, `User`, or `System`, with actor ID, guard, route, method,
IP, bounded user agent, request identifier, and available organization/FY
metadata. Actor identity always uses actor type plus ID; numeric IDs are not
treated as globally unique.

## Logging and coverage

`AuditMutation` centrally records successful important mapped mutations and
unusual state-changing GETs, without logging harmless page views. Security/RBAC
changes, individual permission overrides, Financial Year actions and role
permission changes also use explicit service-level entries. Current mapped
Sales, Purchase, Production/WPR, Inspection, Warehouse, Gate Pass and Job Work
mutations are covered by the same route permission registry.

Use the service for new important actions:

```php
app(AuditLogger::class)->record([
    'module' => 'warehouse',
    'action' => 'adjust',
    'event' => 'stock_adjusted',
    'auditable_type' => WarehouseItem::class,
    'auditable_id' => $item->id,
    'old_values' => ['quantity' => $before],
    'new_values' => ['quantity' => $after],
]);
```

For work inside a transaction use `recordAfterCommit`; a rolled-back
transaction therefore does not leave a successful audit row.

## Redaction and immutability

The logger recursively redacts password, token, OTP, cookie, session, CSRF,
authorization and secret-like keys. Only meaningful before/after values and
changed fields should be supplied; large payloads and file contents are not
logged. `AuditLog` rejects model updates and deletes, and no application route
mutates or purges audit history.

## Viewer and RBAC

Authorized Admin users with `audit-logs.view` can use the read-only paginated
viewer at `/admin/audit-logs`, filter by date, actor, module, action and entity,
and inspect details. Frontend users have no audit-log access. `audit-logs.view`
and `audit-logs.export` are canonical permissions; no delete permission exists.

Audit history should be preserved by default. A future reviewed archive/retention
process may move old records to controlled storage; destructive automatic purge
is deliberately not implemented in Task 1.8.
