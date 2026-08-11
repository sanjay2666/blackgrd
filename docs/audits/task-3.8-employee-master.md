# Task 3.8 — Employee Master Audit

## Existing architecture and live data

The canonical shared party table is `individuals`; there is no separate `employees` table. Its `type` enum distinguishes `employee`, `customers`, `vendors`, `transport`, `agents`, `master`, and `labourer`. Live read-only inspection found 645 Individuals: 17 employees, 248 customers, 194 vendors, 179 transporters, and 7 master rows. Employee IDs are preserved: 1, 2, 3, 4, 5, 16, 29, 31, 356, 359, 361, 397, 581, 585, 593, 601, and 609. All currently inspected employee rows were Active, company 1, with no existing code, Factory, Shift, or Department assignments.

The existing `users` table links accounts through `individual_id`. Live data has two accounts linked to Employee/Individual ID 1: one Admin account and one Frontend User account. This relationship is preserved; Employee Master neither creates accounts nor manages passwords, roles, guards, or Department Access. Existing operational references include Work Order actor fields (`master_ind_id`, process start/end/inspection employee IDs), inspection and warehouse inspection/receiver fields, warehouse compartments, notifications, dispatch receivers, addresses, and User links. No IDs were remapped.

## Implemented contract

Employee Master reuses `Individual` and filters strictly to `type = employee`. The additive migration adds nullable employee code, designation, factory, and shift fields only; no live values are invented. Code is optional and unique for current employees when provided. Active Department, Factory, and Shift IDs are validated server-side within the canonical company. A Factory-specific Shift must match the Employee Factory. Company-global Shifts remain available. Department Factory consistency is enforced.

The admin CRUD supports name, optional code, designation, contact fields, Department, Factory, optional current/default Shift, Active/Inactive status, search, filters, pagination, activation/deactivation, and protected logical removal. Existing active operational `/list_employee` autocomplete remains type- and status-filtered, preserving its JSON contract. Inactive records remain historically resolvable. Linked Frontend User presence is displayed only; User Management remains separate.

Referenced Employees cannot be hard-deleted. Deactivation leaves any linked User lifecycle unchanged and does not rewrite operational history. Department, Factory, and current Shift changes are allowed as current-state metadata; no assignment-history engine is added. Audit Log records meaningful Employee mutations.

## Boundaries

Employee Master defines the company worker/person record; a Frontend User is a separate optional login identity. Employee designation or Department must never be used as a replacement for RBAC authorization. Employee home Department and Frontend User Department Access are separate concepts. Historical operational references to Employee/Individual IDs must never be rewritten when Employee Master changes. Customer, Vendor, Transporter, payroll, attendance, leave, overtime, biometric, roster, salary, HRMS, and authentication tasks remain out of scope.
