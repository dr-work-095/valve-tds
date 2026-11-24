<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenants')) {
            return;
        }

        $tenantUidMissing = ! Schema::hasColumn('tenants', 'tenant_uid');

        if ($tenantUidMissing) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->string('tenant_uid', 64)->nullable()->after('id');
            });

            DB::table('tenants')
                ->orderBy('id')
                ->each(function (object $tenant): void {
                    DB::table('tenants')
                        ->where('id', $tenant->id)
                        ->update(['tenant_uid' => $tenant->id]);
                });
        }

        if ($tenantUidMissing) {
            $usedTenantUids = [];

            DB::table('tenants')
                ->orderBy('id')
                ->get()
                ->each(function (object $tenant) use (&$usedTenantUids): void {
                    $slug = preg_replace('/[^a-z0-9]/', '', strtolower((string) $tenant->company_name)) ?? '';
                    $base = 'tenant_'.($slug !== '' ? $slug : 'company');
                    $tenantUid = $base;
                    $counter = 2;

                    while (isset($usedTenantUids[$tenantUid])) {
                        $tenantUid = $base.$counter;
                        $counter++;
                    }

                    $usedTenantUids[$tenantUid] = true;
                    $oldTenantUid = (string) $tenant->tenant_uid;

                    DB::table('tenants')->where('id', $tenant->id)->update(['tenant_uid' => $tenantUid]);

                    if (Schema::hasTable('users')) {
                        DB::table('users')
                            ->where('tenant_id', $oldTenantUid)
                            ->update(['tenant_id' => $tenantUid]);
                    }
                });

            Schema::table('tenants', function (Blueprint $table): void {
                $table->unique('tenant_uid');
            });
        }

        if (Schema::hasTable('users') && DB::getDriverName() === 'mysql') {
            $idType = DB::getSchemaBuilder()->getColumnType('tenants', 'id');

            if (! in_array(strtolower((string) $idType), ['bigint', 'int', 'integer'], true)) {
                DB::statement('ALTER TABLE users DROP FOREIGN KEY users_tenant_id_foreign');
                DB::statement('ALTER TABLE tenants DROP PRIMARY KEY');
                DB::statement('ALTER TABLE tenants CHANGE COLUMN id old_tenant_id VARCHAR(64) NOT NULL');
                DB::statement('ALTER TABLE tenants ADD COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST');
                DB::statement('ALTER TABLE tenants DROP COLUMN old_tenant_id');
                DB::statement('ALTER TABLE users ADD CONSTRAINT users_tenant_id_foreign FOREIGN KEY (tenant_id) REFERENCES tenants(tenant_uid) ON DELETE CASCADE');
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tenants') && Schema::hasColumn('tenants', 'tenant_uid')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->dropUnique(['tenant_uid']);
                $table->dropColumn('tenant_uid');
            });
        }
    }
};
