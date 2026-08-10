# Financial Year Architecture

Status: implemented foundation for Task 1.5 (2026-08-10)

## Master

`financial_years` is company-scoped through `company_id`. It stores the
four-digit compatibility `code` (`2627`), human-readable `display_name`
(`2026-27`), `start_date`, `end_date`, `is_current`, `locked_at`, audit fields
and the existing `RecordStatus` values. `(company_id, code)` is unique. The
manager validates date order and rejects overlapping active/non-deleted years.
Current-year changes lock the company's rows, clear the old current flag and set
the target atomically; inactive/deleted years cannot become current.

## Resolution and writes

`FinancialYearResolver` resolves the current year from the trusted
`CurrentOrganizationContext` company. It throws `MissingCurrentFinancialYear`
when no active current year exists; it never guesses from the system clock or
trusts request input. The existing `currentFinancialYear()` helper remains as a
compatibility facade and now returns the master code.

Models created through active application flows receive `financial_year_id` and
the canonical legacy string when both columns are present. Existing transaction
controllers continue to use the compatibility helper, so statuses, quantities,
IDs and document behavior remain unchanged.

## Compatibility and history

Existing `CHAR(4)` fields remain in place as immutable historical snapshots.
Selected business tables receive nullable/indexed `financial_year_id` columns;
legacy nulls remain nullable until deterministic evidence exists. The invalid
purchase-order value `2026` was mapped to FY `2627` using its July 2026 purchase
date. No destructive column removal or unrelated master cleanup is included.

The future Number Series task should use `(company_id, financial_year_id,
document_type, optional branch/factory)` and must not mutate historical
financial-year snapshots.
