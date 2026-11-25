<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:expire-overdue-tenants')]
#[Description('Command description')]
class ExpireOverdueTenants extends Command
{
    public function handle(): int
    {
        $updated = 0;

        Tenant::on('central')
            ->whereIn('status', ['trial', 'active'])
            ->whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '<', now())
            ->chunkById(100, function ($tenants) use (&$updated): void {
                foreach ($tenants as $tenant) {
                    $updated += DB::connection('central')
                        ->table('tenants')
                        ->where('id', $tenant->id)
                        ->whereIn('status', ['trial', 'active'])
                        ->whereNotNull('subscription_ends_at')
                        ->where('subscription_ends_at', '<', now())
                        ->update([
                            'status' => 'expired',
                            'updated_at' => now(),
                        ]);
                }
            });

        $this->info("Expired {$updated} overdue tenant(s).");

        return self::SUCCESS;
    }
}
