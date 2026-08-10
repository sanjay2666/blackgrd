# Task 3.2 — Branch / Factory Master audit

The audit found an existing canonical organization foundation: `companies`, `branches`, `factories`, `user_organization_access`, `CurrentOrganizationContext`, and existing factory foreign keys on Department, Warehouse, and Machine. Live branch/factory counts were not used to invent or remap records.

Implementation preserves the separate established Branch and Factory concepts. The additive migration extends both existing tables with practical address/contact fields, optional location GSTIN, and remarks. No company-switching or multi-company behavior was added. The service assigns company ownership from `CurrentOrganizationContext`, validates active parent branches, normalizes GSTIN, and records mutations through AuditLogger. Active/Inactive lifecycle is explicit and no destructive delete endpoint is provided.

Admin routes are protected by the existing admin, organization, RBAC, and audit middleware. `branches.view/create/update/activate/deactivate` are canonical shared permissions, and navigation is permission-aware. Bootstrap 3.3.7-compatible listing and compact forms provide search, status filtering, pagination, separate Branch/Factory code and type presentation, and activation controls.

Schema verification is performed only against disposable `blackgrd_schema_testing`; the live `blackgrd` database remains protected. Maintenance mode remains off. No live backup or migration apply is claimed by this source audit; that requires the reviewed deployment procedure and a verified backup manifest.
