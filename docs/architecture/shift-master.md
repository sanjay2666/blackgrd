# Shift Master

Shift Master defines reusable working-time windows; it does not implement employee attendance, roster scheduling, payroll, or machine scheduling.

The canonical table is `shifts` and the model is `App\Models\Shift`. A Shift is company-owned and may optionally be factory-scoped; an empty factory scope is a company-global default. No Department relationship is added because the reviewed architecture does not define department-specific Shift ownership. The supported fields are name, optional code, `start_time`, `end_time`, optional description, display order, scope, audit metadata, and Active/Inactive/Deleted status. No Financial Year copy is created.

Start and End are stored as database `TIME` values and accepted as `HH:MM`. A Shift crossing midnight, such as 22:00–06:00, is a valid single Shift when business rules require it. Duration is derived for display only: if End is earlier than Start, one day is added. Equal times are rejected; no arbitrary maximum duration or payable-hour calculation is imposed.

Non-deleted Shift names and codes must be unique within the same company/factory scope. Overlaps are allowed because general, departmental, and staggered windows may legitimately overlap. Inactive Shifts remain readable historically but are excluded from new active selections. Referenced Shifts cannot be hard-deleted, and deactivation is blocked when future assignment tables expose active `shift_id` references. Changing a referenced name, code, time, or scope is blocked because changing a referenced Shift must not silently change historical operational meaning.

The current repository/live database had no Shift table, records, `shift_id` references, employee Shift assignment, attendance, roster, or production Shift scheduling. The existing Work Order `shiftWorkOrderToWarping` action and `work_shifted_by` field mean transferring a Work Order to Warping; they are not Shift Master references and remain unchanged. Task 3.8 may add Employee selection against active Shifts later.

Admin CRUD is under Masters using the existing `masters.*` RBAC mapping and permission-aware navigation. Machine Capacity remains independent: no Machine + Shift capacity or scheduling relationship is introduced. Audit Log records create, update, activate, deactivate, and remove mutations with before/after values.
