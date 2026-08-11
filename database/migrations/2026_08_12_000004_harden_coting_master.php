<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('cotings')) {
            return;
        }

        Schema::table('cotings', function (Blueprint $table): void {
            if (! Schema::hasColumn('cotings', 'description')) {
                $table->text('description')->nullable();
            }
            if (! Schema::hasColumn('cotings', 'display_order')) {
                $table->unsignedInteger('display_order')->nullable();
            }
        });

        if (! $this->hasIndex('cotings', 'cotings_status_order_idx')) {
            Schema::table('cotings', fn (Blueprint $table) => $table->index(['status', 'display_order', 'id'], 'cotings_status_order_idx'));
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cotings')) {
            if ($this->hasIndex('cotings', 'cotings_status_order_idx')) {
                Schema::table('cotings', fn (Blueprint $table) => $table->dropIndex('cotings_status_order_idx'));
            }
            Schema::table('cotings', function (Blueprint $table): void {
                if (Schema::hasColumn('cotings', 'display_order')) {
                    $table->dropColumn('display_order');
                }
                if (Schema::hasColumn('cotings', 'description')) {
                    $table->dropColumn('description');
                }
            });
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))->contains(fn (array $value): bool => ($value['name'] ?? '') === $index);
    }
};
