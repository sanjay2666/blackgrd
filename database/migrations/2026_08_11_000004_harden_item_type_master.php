<?php

use App\Services\ItemTypeMasterService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('item_type', function (Blueprint $table): void {
            $table->string('short_code', 30)->nullable()->after('item_type_name');
            $table->unsignedInteger('display_order')->nullable()->after('short_code');
            $table->index(['status', 'display_order'], 'item_type_status_order_idx');
        });
        foreach (ItemTypeMasterService::PROTECTED_IDENTITIES + [6 => 'General', 7 => 'Chemical', 9 => 'Colour'] as $id => $name) {
            $code = ItemTypeMasterService::codeForId((int) $id);
            Schema::getConnection()->table('item_type')->where('item_type_id', $id)->update(['short_code' => $code, 'display_order' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('item_type', function (Blueprint $table): void {
            $table->dropIndex('item_type_status_order_idx');
            $table->dropColumn(['short_code', 'display_order']);
        });
    }
};
