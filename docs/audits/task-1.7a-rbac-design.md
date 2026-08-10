# Task 1.7A — RBAC / Roles & Permissions Design Audit

Date: 2026-08-10
Repository: E:\projects\blackgrd
Live database: blackgrd (read-only audit only)

## Preflight

The repository was clean and aligned at baseline
9f1b8ad4bbf66a70a5d8c5dc213d7b1f00de3e4f, Harden application authentication.

## Current authorization evidence

No roles, permissions, policies, Gates, role pivots, permission middleware, or
RBAC UI exists. Current server-side boundaries are authentication guards and
organization middleware. The admin sidebar and user_web_pages are not
authorization controls.

php artisan route:list --json reported:

| Surface | Count | Boundary |
| --- | ---: | --- |
| Admin routes | 144 | auth:admin, organization |
| Frontend routes | 120 | auth:web, organization |
| Shared authenticated lookup routes | 18 | auth:web,admin, organization |
| Authenticated organization switch | 1 | auth:web,admin |
| Guest/public/storage/health routes | 14 | guest/framework/public |
| Total registered routes | 297 | — |

The active inventory covers dashboard, organization context, company, financial
year, department, process, machine, employee/party, item/reference masters,
sale orders, purchases, work orders, WPR, inspections, warehouse, gate pass,
job work/mill dispatch, reports/prints/exports, settings and security.
Branches/factories have schema foundations but no live rows or CRUD routes.

## Live read-only evidence

Existing access-related tables are users, user_organization_access,
user_activity_logs, user_web_pages, login_attempts, and login_otps. There are
no role or permission tables.

| Evidence | Result |
| --- | ---: |
| Active frontend users | 1 |
| Active admin users | 1 |
| Active organization mappings | 2 |
| Active companies | 1 |
| Branches | 0 |
| Factories | 0 |
| Login-attempt rows | 0 |
| Login-OTP rows | 0 |

No password, hash, token, session identifier or other sensitive value is
included in this report.

## Design result

The recommended implementation is multiple company-scoped roles per user,
global version-controlled permissions, role-permission pivots, and scoped
user-role assignments. Super Admin is an explicit reserved system role;
Company Admin is a company role and cannot grant or emulate Super Admin.
Organization access remains a separate prerequisite intersected with role scope.

The complete schema, matrix, route mapping, bootstrap plan, security invariants,
cache strategy and Prompt 12 sequence are in
docs/architecture/rbac-roles-permissions.md.

## Scope and safety

This task creates documentation only. No RBAC migration, model, middleware,
policy, seeder, UI, route change, user-role assignment, or database write was
performed. Prompt 12 implementation and later Audit Log/Approval/Workflow work
remain unstarted.
