# Project Instructions

- Actual project: `E:\projects\blackgrd`.
- `E:\xampp\htdocs\erp` is a read-only business-flow reference; never modify it.
- Analyze once, implement the complete requested task, verify, report, and stop. Do not start later roadmap tasks automatically.
- Treat live database `blackgrd` as protected. Never disable or weaken `DatabaseSafetyGuard` or run destructive commands against it.
- Use disposable database `blackgrd_schema_testing` and reviewed/hash-pinned migrations with backups and preservation checks before live schema/data changes.
- Keep reusable organization/business logic in services, actions, or support classes; avoid controller-private business helpers.
- Keep Eloquent queries simple/readable, do not put DB queries in Blade, and preserve the Bootstrap 3.3.7 UI and existing business behavior unless required.
- Run task-scoped Pint, tests, and `git diff --check`; do not modify unrelated formatting debt merely to pass a gate.
- Keep architecture and audit documentation under `docs/architecture` and `docs/audits`; architecture documents are the source of truth.
- Preserve company isolation, canonical statuses, and Sale Order Item-specific/versioned printing routes; Coating Master never decides printing order.
- AI and later roadmap tasks are out of scope unless explicitly requested.
