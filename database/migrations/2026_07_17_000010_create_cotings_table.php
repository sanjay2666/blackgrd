<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->dateTime('created')->nullable();
            $table->dateTime('modified')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
        });

        DB::table('cotings')->insert([
            [
                'code' => 'AF',
                'name' => 'AF Coating',
                'created' => now(),
                'modified' => now(),
                'status' => 'Active',
            ],
            [
                'code' => 'PUWR',
                'name' => 'PU Coated WR',
                'created' => now(),
                'modified' => now(),
                'status' => 'Active',
            ],
            [
                'code' => 'PU',
                'name' => 'PU',
                'created' => now(),
                'modified' => now(),
                'status' => 'Active',
            ],
            [
                'code' => 'PVC',
                'name' => 'PVC',
                'created' => now(),
                'modified' => now(),
                'status' => 'Active',
            ],
            [
                'code' => 'WR',
                'name' => 'Water Repellant',
                'created' => now(),
                'modified' => now(),
                'status' => 'Active',
            ],
            [
                'code' => 'WRC',
                'name' => 'Water Repellant Cire',
                'created' => now(),
                'modified' => now(),
                'status' => 'Active',
            ],
            [
                'code' => 'NO',
                'name' => 'No Coating',
                'created' => now(),
                'modified' => now(),
                'status' => 'Active',
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('cotings');
    }
};

