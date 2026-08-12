# Task 4.5 Coating Printing Route Decision audit

## Starting point and inspection

Baseline was `9522bc88844d834165a468d83d4f8e0ef4eceb5a`. The current Laravel implementation already had the per-Work-Order `work_orders.print_position` enum and a `decide-printing-position` route, but the page showed the controls outside the Coating context, the save endpoint did not check department access or lock the Work Order, and the `before` choice did not create a Printing stage. No old ERP Printing routing was reused because the approved Task 4.5 rules replace it.

The active Process Master preserves D-Printing for dyed material and C-Printing for coated material. Existing Coating inspection already creates C-Printing for `after`; its normal downstream flow remains intact.

## Implementation

- `/show-workorders` identifies Coating processes from the current accessible Process Master records and shows the three compact choices only on those rows.
- The route decision is stored in the existing per-Work-Order `print_position` field. No schema or data migration was required.
- `Printing Before Coating` locks the active Coating Work Order, creates exactly one D-Printing child and copies only that Work Order's active item rows. The existing D-Printing inspection path then creates the return Coating child.
- `Coating Before Printing` keeps the existing Coating completion behavior, which creates the existing C-Printing child.
- `No Printing Required` leaves the existing normal downstream Coating behavior unchanged.
- The decision endpoint requires authenticated RBAC middleware, current-company Work Order scope, authorized department access to the Coating process, pending inspection, and no inspection/child Work Order history. It uses a manual transaction and locks the Work Order to prevent duplicate child creation.

## Compatibility and verification

`/show-saleorderitems` and the Weaving-to-Warping action were not changed. All other departments retain their existing Request action on the common Work Order listing. Focused route-decision tests cover the three choices, Coating-only UI visibility, department enforcement markers, parent/child continuation, and duplicate protection markers. No live migration or live database change was performed.
