# Centralized Number Series

Number allocation is provided by `App\Services\NumberSeriesService`. A caller uses
`app(NumberSeriesService::class)->next('job-work-voucher')` for formatted output or
`nextInteger('work-order-2')` for legacy integer serials. Allocation runs in a database
transaction and locks the applicable `number_series` row with `FOR UPDATE`; the counter
is incremented before the value is returned. A rollback may leave a gap when an allocation
has already been committed, and values are never deliberately reused.

The table is single-company. Financial-year-aware rows are keyed by series and financial
year; a missing year row is cloned from the template with sequence 1. The current ERP
series are deliberately configured as non-FY-reset because their established formats and
business meaning are lifetime/process counters. `{FY}` is supported in prefix and suffix
for future series that are explicitly configured as FY-aware.

## Current inventory

| Series key | Existing format | Reset | FY-aware | Storage field | Generator |
| --- | --- | --- | --- | --- | --- |
| `work-order-1..4` | process code + integer serial (`W1`, `V1`, `D1`, `C1` style) | Never | No | `work_orders.process_sl_no` | `WorkOrderController` |
| `wpr-lot` | numeric lot | Never | No | `work_process_requirements.req_lot_no` | `WorkOrderController` |
| `job-work-voucher` | numeric voucher | Never | No | `stock_mill_dispatches.voucher_number` | `JobMillWorkController` collision fallback |
| `job-work-challan` | numeric challan | Never | No | `stock_mill_dispatches.chalan_no` | `JobMillWorkController` collision fallback |

Sale Order numbers, purchase invoice/challan numbers, and job-work values entered by the
operator remain manual/external references. Gate-pass numbers are legacy work-order
serial references, not an independent allocator. Inspection/lab-test numbers and other
warehouse invoice references currently have no system generator and were not invented.

Existing records are not rewritten. The seed migration reads the highest numeric legacy
value and initializes `next_number` to highest + 1. Non-numeric legacy values are not
safe to parse and require an explicit reviewed bootstrap adjustment before enabling a
corresponding series.

Administration is available at `/admin/number-series`, protected by
`number-series.view` and `number-series.manage`. Counters are forward-only: a lower
`next_number` is rejected and there is no reset-all operation. Changes are recorded by
the Task 1.8 audit logger.

To add a series safely: document its existing generator and format, add a reviewed seed
row with a read-only highest-value bootstrap, add focused tests, migrate only future
generation, and add a unique index only after a duplicate audit proves it is safe.
