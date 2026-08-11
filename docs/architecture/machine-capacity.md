# Machine Capacity

Machine Capacity is a company-scoped child configuration of the canonical Machine Master. It stores one current configuration per machine: a positive `capacity_value` and a canonical active `unit_type_id` from the existing Unit Master. It does not alter machine identity or any historical Machine, Work Order, Dyeing Planning, inspection, or warehouse assignment.

The configuration is intentionally neutral about time, shifts, throughput, utilization, availability, and scheduling. Those concerns remain outside this foundation and no runtime consumer is introduced by Task 3.6. Existing machines with no configuration remain valid, and no capacity values are seeded or inferred.

Only active machines and active units may be selected for new or changed configurations. Inactive machines and units remain readable where already referenced, but cannot be used to create or change a configuration. Duplicate non-deleted configurations for one company and machine are rejected by the service. Removal is a logical `Deleted` transition and is audited.

The admin page is available under Masters as Machine Capacity, protected by the existing `masters.view` / `masters.create` / `masters.update` / `masters.delete` permissions through the standard resource mapping. Company scoping is enforced by the model and service. No foreign-key rewrite or historical backfill is performed.
