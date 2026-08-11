# Chemical Master

Chemical Master reuses the canonical `items` table and `App\Models\Item` model. A Chemical is an Item whose `item_type_id` resolves by normalized name to the existing active `Chemical` row in `item_type` (the verified repository mapping is ID 7 / `CHEMICAL`). No independent Chemical table, duplicate code source, or duplicate unit/tax source exists.

The master exposes Item name as Chemical Name, Item code as Chemical Code, canonical Unit Master, optional HSN and GST references, description/specification (`remarks`), and Active/Inactive status. Units are labels only; no conversion engine or density assumption is introduced. HSN/GST remain Item references. Logical duplicate prevention is normalized Chemical name within the Chemical Item Type and globally unique non-deleted Item Code, without merging legacy records or adding an unsafe constraint.

Existing Item IDs and all history are preserved. A referenced Chemical cannot be hard-deleted; the UI/service deactivates it. An operationally referenced Chemical cannot change name, code, Unit, or Item classification. Safe metadata changes remain auditable. Inactive Chemicals remain readable for historical joins but the new Chemical options endpoint returns active Chemicals only.

The Chemical service checks current Item-based references across sales, work, purchase, warehouse, stock, gate-pass, job-work, and Item Yarn Requirement tables. It does not calculate stock or create a Chemical stock ledger. Purchase, Warehouse, Dyeing, and Coating continue to use their existing Item and snapshot contracts.

**Chemical Master defines reusable Chemical identity. Dyeing/Coating formulas and actual production consumption remain separate transactional/configuration concerns.** Dyeing Lab Test formula rows and their `material_type`, `material_name`, `unit`, and quantity snapshots are not rewritten by Chemical changes. No Dye Formula, Shade Recipe, coating formula, production consumption calculation, or workflow engine was introduced.

Admin routes use the Admin guard, organization scope, audit middleware, and canonical `masters.*` permissions. Navigation is permission-aware under Masters; Frontend Users do not receive Chemical administration. Meaningful create, update, deactivation, activation, and deletion decisions use centralized Audit Log entries with before/after values. Lookups are not audited.
