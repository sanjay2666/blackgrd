# Task 1.6 — Authentication Hardening

Date: 2026-08-10
Repository: `E:\projects\blackgrd`
Live database: `blackgrd`
Disposable verification database: `blackgrd_schema_testing`

## Audit result

The application uses `App\Models\User` with separate `web` and `admin`
session guards and one Eloquent provider. Existing active records were
preserved. The live read-only audit found one active frontend user and one
active admin, no login-attempt rows, no login-OTP rows, two organization access
mappings, and no evidence that the dormant OTP flow is routed into login.

The main gaps were missing login throttling, unsafe direct organization-session
writes in the legacy switch middleware, non-generic reset-request responses,
and redirect handling that relied directly on Laravel's intended URL. The
admin login message also disclosed account-state wording unnecessarily.

## Implemented

- Named login and password-reset rate limiters were added and attached to all
  relevant POST routes, with both IP-wide and IP/email limits.
- Login validates active account type/status, uses Laravel hashing, regenerates
  the authenticated session, and safely rehashes only after successful login.
- Failed login and reset-request responses are generic.
- Logout invalidates the session, regenerates CSRF, and clears organization
  context.
- Safe internal redirect handling rejects external intended destinations.
- Organization context rejects stale/forged company or factory values, checks
  factory ownership/status, and clears invalid session context.
- Registration/reset password validation now requires confirmation plus a
  12-character mixed-case/number/symbol Laravel password rule.
- Password reset tokens remain hashed, expire after 60 minutes, are single-use,
  and invalidate database-backed sessions after reset.
- The admin login page no longer displays a default credential.

## Compatibility and deferred items

Existing password hashes and remember-token behavior remain compatible. No
passwords, hashes, tokens, or session identifiers are documented. OTP remains
dormant and unrouted, so it was not reactivated. No database schema or live
data changes were required. Full RBAC, account lockout persistence, MFA/OTP,
audit-log integration, and global session revocation remain deferred.

## Verification

The live safety check remained correctly blocked for `blackgrd`; no destructive
live operation was run. Task-scoped Pint passed. PHPUnit and route/cache
verification are recorded in the task handoff.
