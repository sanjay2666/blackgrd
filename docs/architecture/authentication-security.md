# Authentication Security Architecture

Status: implemented hardening for Task 1.6 (2026-08-10)

## Architecture

The application has two intentional login panels. The frontend uses the `web`
guard and `User` model; the Admin Panel uses the `admin` guard and the
Admin-panel `Admin` model view over the existing Admin-discriminated account
records. Both retain the existing `users` storage and `user_type` protection,
but their providers and session guards are separate. Frontend login accepts
only `User` and Admin login only `Admin`, both with `status = Active`.
`Deleted` and `Inactive` identities therefore cannot authenticate. Passwords
use Laravel's `hashed` cast and are never returned by either model.

## Login and logout

Both POST login routes use the named `auth-login` limiter. Credentials are
validated server-side, failed responses are generic, successful authentication
regenerates the session ID, and an existing weak hash is rehashed only after a
successful password check. The remember-token option remains supported by
Laravel's session guards.

Logout logs out the active guard, removes organization session values,
invalidates the session, and regenerates the CSRF token.

## Rate limiting and redirects

Login attempts are limited to 20 per minute per IP and five per minute per
IP/email key. Password-reset requests and submissions are limited to 10 per
minute per IP and three per minute per IP/email key.
Authentication redirects accept only relative internal destinations or an
absolute URL matching the current host and scheme; unsafe intended URLs fall
back to the appropriate dashboard.

## Passwords and reset flow

New registration and reset passwords require confirmation and Laravel's
production password rule: at least 12 characters, mixed case, a number and a
symbol. Existing valid hashes remain compatible. Successful authentication
rehashes only when Laravel reports that the stored hash needs it; no bulk
rehash or password reset is performed.

Password-reset tokens remain hashed, expire after 60 minutes, are single-use,
and are removed after successful use. Request responses are generic so the
presence of an email account is not disclosed. Resetting a password removes
the user's database-backed sessions.

## Organization context

After authentication, `ResolveOrganizationContext` resolves the identity's
active `user_organization_access` mapping. Company and factory values are
session context, not grants. Every selected company must be assigned to the
identity; a selected factory must be active and belong to that company. Invalid
or stale values are cleared and the request fails closed. Login clears stale
organization values before establishing a fresh session context.

RBAC uses the existing guard split. Admin and User principals have separate
panel-tagged role assignments; an Admin assignment cannot collide with a User
assignment that happens to have the same numeric record ID. No identity-linking
between a person's Admin and User records is introduced.

## OTP and logging status

The existing `login_otps` table and admin listing are dormant and have no login
or verification route. OTP has not been reactivated. The authentication code
does not write plaintext passwords, OTPs, reset tokens or session identifiers to
the login-attempt table or application logs. The legacy `password_enc` column
in `login_attempts` remains unused for compatibility and is not populated by
the application.

## Session and cookie policy

The database session driver and existing 120-minute idle lifetime remain in
place. Cookies are HTTP-only and SameSite=Lax by default. Secure cookies remain
environment-controlled through `SESSION_SECURE_COOKIE`, allowing local HTTP
development while enabling HTTPS-only cookies in production.

## Deferred work

Full RBAC, explicit Super Admin audited cross-company reporting, audit-log
events, account lockout persistence, MFA/OTP activation, email delivery
infrastructure, and global session revocation policy remain later work. No
schema migration was required for this hardening task.
