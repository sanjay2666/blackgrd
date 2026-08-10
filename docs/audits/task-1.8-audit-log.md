# Task 1.8 — Centralized Audit Log

Status: implementation in progress until the reviewed additive migration,
verification, live apply, tests and push complete.

## Existing mechanisms reviewed

The ERP already has `created_by`/`modified_by` business fields, `login_attempts`
for authentication telemetry, and `user_activity_logs` for activity records.
Those remain intact. Task 1.8 adds one centralized business/security audit
stream rather than duplicating or replacing them.

## Delivered design

- `audit_logs` is append-only at the application layer and indexed for actor,
  module/action, entity, timestamp and Financial Year searches.
- Admin, Frontend User and System actors are explicit.
- `AuditLogger` redacts secrets and supports after-commit recording.
- `AuditMutation` uses the existing exact route/action registry and records only
  important successful mutations/state changes.
- RBAC gains `audit-logs.view` and `audit-logs.export`; total canonical count is
  127. No audit delete capability exists. The exact authenticated route map now
  covers 295 current routes; the two audit viewer routes require the Admin-only
  `audit-logs.view` permission.
- Admin-only paginated viewer and details page are read-only and query data in
  the controller, never Blade.

## Verification and rollout

The live migration is additive and must be applied only through the reviewed
hash-pinned command with the existing verified backup manifest, maintenance
mode and preservation snapshot. Existing login/RBAC/organization systems,
including User-specific Allow/Deny overrides, remain unchanged.

Future important actions should call `AuditLogger` with concise before/after
values. Do not pass request payloads blindly; never pass passwords, tokens,
sessions, OTPs, cookies, CSRF values or file contents.
