# Task 3.7 — Shift Master Audit

## Existing architecture

The baseline at `4e07254` contained no `shifts`, `shift_master`, or `shift_masters` table, model, controller, route, or live Shift record. Live read-only schema inspection found no `shift_id`, `shift_name`, `shift_start`, or `shift_end` column. `individuals` has no Shift relationship, and Work Orders have `work_shifted_by`, which records the existing Weaving-to-Warping Work Order transfer employee—not a working Shift. No attendance, overtime, roster, machine schedule, or production schedule engine exists.

The repository’s organization-scope source of truth classifies Machine / capacity / shift as factory-specific, with possible company defaults. Task 3.7 therefore introduces nullable `factory_id`: a factory-specific Shift when set, or a company-global default when unset. No Department mapping is introduced.

## Implemented contract

The canonical `shifts` table stores no invented live data. It contains Shift name, optional code, database TIME start/end, description, display order, company/factory scope, audit metadata, and Active/Inactive/Deleted status. Start and End are normalized server-side as `HH:MM:SS`. A Shift crossing midnight, such as 22:00–06:00, is a valid single Shift when business rules require it. Duration is derived across midnight for display and is not a payroll or attendance calculation.

Exact logical duplicates are rejected by case-insensitive name and optional code within company/factory scope. Overlapping windows are allowed; no overlap engine was justified. Equal start/end values are rejected, but no arbitrary maximum Shift length is imposed. There are no existing IDs to preserve and no live mappings to rewrite.

Active and Inactive lifecycle is supported. Inactive records remain historically readable and are not offered by future active selectors. Referenced records cannot be hard-deleted. If a future operational table exposes `shift_id`, active-reference deactivation is blocked and referenced identity/time/scope changes are blocked to protect historical meaning. Audit Log records meaningful mutations.

The compact Bootstrap 3.3.7 admin page provides search, status and factory-scope filtering, pagination, create/edit, activation/deactivation, and logical removal. It is protected by the canonical Admin `masters.*` RBAC mapping and appears once under Masters. Frontend users do not receive Shift Master authorization.

## Explicit boundaries

Shift Master defines reusable working-time windows; it does not implement employee attendance, roster scheduling, payroll, machine scheduling, Machine + Shift capacity, production scheduling, overtime, biometric integration, or employee assignment. Task 3.8 Employee Master is next. The existing Work Order transfer route is preserved. No Financial Year duplication is performed.
