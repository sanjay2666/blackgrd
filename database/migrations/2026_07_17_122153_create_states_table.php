<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Historical no-op: 2026_07_17_000002_create_states_table.php owns
        // the states table. This filename remains because it is in the ledger.
    }

    public function down(): void
    {
        // Do not drop the table owned by the canonical states migration.
    }
};
