<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('user_department_access', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('company_id');
            $table->unsignedBigInteger('department_id');
            $table->enum('status', ['Active', 'Inactive', 'Deleted'])->default('Active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'company_id', 'department_id'], 'uda_user_company_department_unique');
            $table->index(['company_id', 'department_id', 'status'], 'uda_company_department_status_idx');
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->restrictOnDelete();
        });

        // Only an explicit legacy organization department is unambiguous enough to backfill.
        DB::table('user_organization_access as organization_access')
            ->join('departments', 'departments.id', '=', 'organization_access.department_id')
            ->whereNotNull('organization_access.department_id')
            ->where('organization_access.status', 'Active')
            ->where('departments.company_id', DB::raw('organization_access.company_id'))
            ->where('departments.status', '!=', 'Deleted')
            ->select([
                'organization_access.user_id', 'organization_access.company_id',
                'organization_access.department_id', 'organization_access.created_by',
            ])->orderBy('organization_access.id')->each(function (object $row): void {
                DB::table('user_department_access')->insertOrIgnore([
                    'user_id' => $row->user_id, 'company_id' => $row->company_id,
                    'department_id' => $row->department_id, 'status' => 'Active',
                    'created_by' => $row->created_by, 'created_at' => now(), 'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_department_access');
    }
};
