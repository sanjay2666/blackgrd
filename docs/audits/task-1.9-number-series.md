# Task 1.9 Number Series Audit

## Scope and preservation

The audit was performed against the Task 1.8 baseline (`1df301e`). Existing document
values remain untouched. Sale Order numbers and purchase invoice/challan references are
operator/customer/vendor supplied and were intentionally excluded from automatic series.

## Findings and migration

The legacy generators were `MAX(CAST(...))+1` for WPR lots and Job Work voucher/challan
collision fallbacks, plus an unlocked `process_items.process_sl_no_last` increment for
Work Orders. These future paths now use `NumberSeriesService`; historical fields and the
legacy process counter are retained for compatibility. Job Work manual values continue
to be accepted; only collision fallback is centralized.

Series keys, formats, reset rules, and storage locations are maintained in
`docs/architecture/number-series.md`. No independent series was created for Gate Pass,
inspection, lab test, warehouse invoice, or external purchase references because the
current code does not generate those identifiers independently.

## Safety

`number_series` uses a unique series/year key, row locking, and forward-only counters.
The allocation transaction prevents two requests from receiving the same counter. A
failed outer business transaction may produce a gap; the counter is never rolled back
to reuse an issued value. Existing document-number columns were not given unique
constraints in this task because a live duplicate audit is required first and no data
may be deleted or renumbered.

The seed migration bootstraps from actual rows using numeric casts and starts at the
highest value plus one. This is additive and preserves records. It must be reviewed on
the disposable database and preflighted against live data before any live apply. No live
database change, fake business document, or maintenance-mode change was performed by
this repository change.

## Administration and audit

The Bootstrap 3.3.7 admin page exposes format, padding, next counter, reset policy, and
status. `number-series.view` and `number-series.manage` are admin-only permissions.
Counter lowering is rejected in both validation and a locked manager transaction.
Configuration changes use `AuditLogger::recordAfterCommit`; routine allocations are not
logged individually.
