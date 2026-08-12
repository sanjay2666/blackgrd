# Task 5.3 Optional, Skip and Repeat Workflow Steps audit

## Scope

Task 5.3 extends only Workflow Version definition and rule semantics. It does not create Work Orders, store execution state, implement rework, add runtime loops, or alter legacy Work Order handlers.

## Schema and compatibility

`2026_08_12_000015_add_optional_steps_and_repeat_support_to_workflow_version_steps` adds non-null `is_required` with default `true` and removes only the old unique `workflow_version_id + process_id` index. The unique `workflow_version_id + sequence` index remains. Existing Workflow Version Steps therefore remain Required and preserve their original linear behavior.

The reverse migration fails before any schema change if optional Step semantics or repeated occurrences exist, preventing silent loss of newer route data while restoring the old Process uniqueness constraint.

## Route semantics

Required Steps cannot be jumped. An Optional Step can be skipped only when every intervening occurrence is Optional; the immediate adjacent Step remains the normal next Step. Consecutive Optional occurrences therefore yield deterministic forward candidates only until the next Required occurrence. Every adjacent edge and every resulting skip edge must be configured in `process_item_allowed_next` at publication time. Selected runtime edges are rechecked against the same configuration.

Repeated Processes are distinct `WorkflowVersionStep` occurrences. Process-only lookup remains available only for unambiguous routes. A repeated Process requires the Step model identity and fails closed rather than guessing the current or selected occurrence.

## Disposable MySQL verification

The exact migration was applied only to `blackgrd_schema_testing` with DatabaseSafetyGuard armed for that named disposable database. Schema inspection confirmed `is_required tinyint(1) NOT NULL DEFAULT 1`, preservation of the unique Version/sequence index, and removal of the Version/Process index. The migration was rolled back to Pending, the prior unique Version/Process index was confirmed, then it was reapplied. Business-table count and ordered-ID hash snapshots for companies, Process Items, Sale Orders, Sale Order Items, Work Orders, and Work Order Items remained unchanged (all empty in the disposable baseline).

No live `blackgrd` migration was attempted. Its protected DatabaseSafetyGuard policy requires the separately operated reviewed live deployment procedure, verified backups, maintenance mode, and writer shutdown; this task does not weaken or bypass that protection.
