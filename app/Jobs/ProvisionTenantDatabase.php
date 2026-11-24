<?php

namespace App\Jobs;

use App\Models\Tenant;
use Database\Seeders\TenantDatabaseSeeder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Stancl\Tenancy\Jobs\CreateDatabase;
use Throwable;

class ProvisionTenantDatabase implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 240;

    public array $backoff = [10, 60, 180];

    public int $uniqueFor = 3600;

    public function __construct(public int $tenantId)
    {
        $this->onQueue('provisioning');
    }

    public function handle(): void
    {
        $tenant = Tenant::on('central')->findOrFail($this->tenantId);
        $tenantUid = (string) $tenant->tenant_uid;
        $databaseName = $tenant->database()->getName();

        if (! preg_match('/\Atds_tenant_[a-z0-9]{1,57}\z/', (string) $databaseName)) {
            throw new RuntimeException('The generated tenant database name is invalid.');
        }

        $databaseManager = $tenant->database()->manager();

        if ($tenant->provisioningStatus() === 'ready' && $databaseManager->databaseExists($databaseName)) {
            $this->createPrivateDirectories($tenantUid);

            return;
        }

        $tenant->setInternal('db_name', $databaseName);
        $tenant->setInternal('provisioning_status', 'provisioning');
        $tenant->setInternal('provisioning_error', null);
        $tenant->save();

        if (! $databaseManager->databaseExists($databaseName)) {
            CreateDatabase::dispatchSync($tenant);
        }

        try {
            $migrationExitCode = Artisan::call('tenants:migrate', [
                '--tenants' => [$tenantUid],
                '--no-interaction' => true,
            ]);

            if ($migrationExitCode !== 0) {
                throw new RuntimeException('Tenant database migrations failed: '.Artisan::output());
            }

            tenancy()->initialize($tenant);

            app(TenantDatabaseSeeder::class)->run();

            $this->createPrivateDirectories($tenantUid);
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
        }

        $tenant->setInternal('db_name', $databaseName);
        $tenant->setInternal('provisioning_status', 'ready');
        $tenant->setInternal('provisioning_error', null);
        $tenant->save();
    }

    public function uniqueId(): string
    {
        return $this->tenantId;
    }

    public function uniqueVia(): Repository
    {
        return cache()->driver('database');
    }

    public function failed(?Throwable $exception): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        $tenant = Tenant::on('central')->find($this->tenantId);

        if (! $tenant) {
            Log::error('Provisioning failed after its tenant record was removed.', [
                'tenant_id' => $this->tenantId,
                'exception' => $exception,
            ]);

            return;
        }

        $tenant->setInternal('provisioning_status', 'failed');
        $tenant->setInternal('provisioning_error', 'Provisioning failed. Check application logs and retry.');
        $tenant->save();

        Log::error('Tenant database provisioning failed.', [
            'tenant_id' => $this->tenantId,
            'exception' => $exception,
        ]);
    }

    private function createPrivateDirectories(string $tenantId): void
    {
        foreach (['logos', 'schematics', 'datasheets'] as $directory) {
            if (! Storage::disk('tenant_private')->makeDirectory($tenantId.'/'.$directory)) {
                throw new RuntimeException("Unable to create the tenant {$directory} directory.");
            }
        }
    }
}
