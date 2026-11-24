<?php

namespace App\Support;

use App\Jobs\ProvisionTenantDatabase;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Throwable;

class TenantProvisioningDispatcher
{
    public function dispatch(Tenant $tenant): bool
    {
        try {
            ProvisionTenantDatabase::dispatch((int) $tenant->getKey());

            return true;
        } catch (Throwable $exception) {
            $tenant->setInternal('provisioning_status', 'failed');
            $tenant->setInternal('provisioning_error', 'Provisioning could not be queued. Check application logs.');
            $tenant->save();

            Log::error('Tenant provisioning could not be queued.', [
                'tenant_id' => $tenant->getKey(),
                'exception' => $exception,
            ]);

            return false;
        }
    }
}
