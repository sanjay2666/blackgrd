# Shared Status Foundation

## Purpose and status categories

This foundation keeps three different concepts separate:

- `RecordStatus` describes whether a master record is `Active`, `Inactive`, or logically `Deleted`.
- `AccountStatus` describes whether a user/admin account is `Active`, `Inactive`, `Locked`, or `Disabled`.
- `MachineOperationalState` describes whether a machine is `Available`, `Running`, `Idle`, in `Maintenance`, in `Breakdown`, or `Blocked`.

Do not add sale-order, purchase-order, work-order, inspection, inventory-posting, job-work, packaging/dispatch, or approval states to `RecordStatus`. Values such as `Pending`, `Complete`, `Completed`, `Accepted`, and `Approved` are business lifecycle states and require their own bounded enums in the relevant module.

`AccountStatus` and `MachineOperationalState` are design foundations only in Task 1.3B. The current `users.status` column allows `Active`, `Inactive`, and `Deleted`, so it cannot safely persist `Locked` or `Disabled`. The current `machines` table has no dedicated operational-state column. No unsupported value is written and no schema change was made.

## Record status contract

The migrated master tables all use the verified contract:

```text
enum('Active','Inactive','Deleted') NOT NULL DEFAULT 'Active'
```

`RecordStatusCast` writes a canonical database string and returns a canonical string to application code. Returning a string preserves existing strict comparisons in controllers and Blade pages while assignment remains validated by `RecordStatus`. Invalid values throw instead of silently becoming active.

The shared `HasRecordStatus` trait supplies the cast and these local scopes:

```php
Company::active()->get();
Machine::inactive()->get();
Warehouse::notDeleted()->get();
```

The trait is only for models whose `status` column has the master-record contract. It must not be placed on models where `status` represents a business lifecycle.

## Legacy compatibility

Only the master-record compatibility layer applies this mapping:

| Input | Canonical stored value |
| --- | --- |
| `1` or `"1"` | `Active` |
| `0` or `"0"` | `Inactive` |
| `Active` (case-insensitive) | `Active` |
| `Inactive` (case-insensitive) | `Inactive` |
| `Deleted` (case-insensitive) | `Deleted` |

Whitespace around string input is ignored. `null`, booleans, other numeric values, empty strings, and business states are invalid. `tryFromLegacyValue()` returns `null` for invalid input; `fromLegacyValue()` and the model cast throw `InvalidArgumentException`.

Normal create/edit forms use `RecordStatusRule`, which accepts canonical and legacy `1/0` input but intentionally rejects `Deleted`. Delete remains an explicit endpoint. The shared Blade partial receives `RecordStatus::formOptions()` from its controller and displays only `Active` and `Inactive`.

## Transition rules

`RecordStatusTransition` and the model trait enforce:

| From | To | Result |
| --- | --- | --- |
| Active | Inactive | Allowed |
| Inactive | Active | Allowed |
| Active | Deleted | Allowed |
| Inactive | Deleted | Allowed |
| Same state | Same state | Allowed/no-op |
| Deleted | Active or Inactive | Blocked |

An explicit restore path can call the transition service with `explicitRestore: true` in a future dedicated restore action. Task 1.3B does not add such an endpoint.

Eloquent model saves enforce transitions. Query-builder bulk updates do not run model events and therefore must not be used to change record status. Future services must load the model and save it through Eloquent, or explicitly call the transition service.

## Modules migrated in Task 1.3B

The cast, scopes, transition protection, reusable validation, canonical form options, and selected controller filters are applied to:

- Company (`companies`)
- Department (`departments`)
- Process (`process_items` / `ProcessItem`)
- Machine record status (`machines`)
- Item (`items`)
- Colour (`colours`)
- Warehouse (`warehouses`)
- Warehouse Compartment (`warehouse_compartments`)
- Unit (`unit_type` / `UnitType`)
- Item Type (`item_type` / `ItemType`)
- Coating Type (`cotings` / legacy `Coting` naming)

Branch/Factory was not created because no current module/table exists. User/Admin account persistence remains pending because its schema does not support all `AccountStatus` values. Machine operational-state persistence remains pending because no dedicated column exists.

## Live read-only verification (2026-08-03)

The `blackgrd` database was queried read-only. Every selected status column matched the non-null enum contract above. Stored distributions were:

| Table | Stored values and counts |
| --- | --- |
| companies | Active 1 |
| departments | Active 3 |
| process_items | Active 7, Inactive 1 |
| machines | Active 10 |
| items | Active 589 |
| colours | Active 3 |
| warehouses | Active 7, Inactive 1 |
| warehouse_compartments | Active 186, Inactive 39 |
| unit_type | Active 2, Deleted 2 |
| item_type | Active 9 |
| cotings | Active 7 |
| users | Active 2 |

No null, numeric, empty, or otherwise invalid stored status was found, so there are no cleanup IDs for this task. No live row or schema was changed. The database safety check reported `BLOCKED` for destructive operations on `blackgrd`.

## Usage examples

Normalize external master-record input:

```php
$status = RecordStatus::fromLegacyValue($request->input('status'));
// $status->value is the canonical database value.
```

Validate an ordinary create/edit status field:

```php
'status' => ['required', new RecordStatusRule],
```

Validate an internal action which deliberately accepts `Deleted`:

```php
'status' => ['required', new RecordStatusRule(allowDeleted: true)],
```

Check a transition before a future explicit operation:

```php
RecordStatusTransition::ensureAllowed($current, $next);
```

## Deletion semantics

`status = Deleted` is the existing logical record-state convention; it is not Laravel `SoftDeletes`. Task 1.3B adds no `deleted_at` column, does not enable `SoftDeletes`, does not change hard-delete behavior, and does not refactor delete endpoints. Normal list scopes exclude `Deleted`, and ordinary forms cannot select it.

## Work pending for Original Prompt 6 and later module tasks

Operational conversion remains pending for sale orders, purchase orders, work-order execution, work-process requirements, inspections, inventory posting, job work, packaging/dispatch, and approval workflow. The future workflow engine must also support a per-fabric or per-sale-order-item route (for example Dyeing → Printing → Coating versus Dyeing → Coating → Printing); printing position must not be stored in Coating Master.

Future developers must:

1. Define a bounded enum per business lifecycle rather than extending `RecordStatus`.
2. Audit the real column, existing values, queries, forms, and transitions before applying a cast.
3. Never default unknown input to `Active`.
4. Keep account state, machine operational state, record visibility, and business execution state separate.
5. Add schema support in a dedicated reviewed task before persisting currently unsupported account or machine states.
6. Preserve the database safety guard and use only a disposable allow-listed database for write tests.
