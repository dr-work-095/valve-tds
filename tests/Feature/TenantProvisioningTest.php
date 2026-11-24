<?php

namespace Tests\Feature;

use App\Jobs\ProvisionTenantDatabase;
use App\Models\Tenant;
use App\Support\TenantPlanLimits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class TenantProvisioningTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var list<string>
     */
    private array $tenantDatabaseFiles = [];

    protected function tearDown(): void
    {
        DB::purge('tenant');

        foreach ($this->tenantDatabaseFiles as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    public function test_provisioning_creates_the_tenant_database_and_seeds_idempotently(): void
    {
        $tenant = $this->createTenant();
        $databaseName = $tenant->database()->getName();
        $this->tenantDatabaseFiles[] = database_path($databaseName);
        Storage::fake('tenant_private');
        $job = new ProvisionTenantDatabase($tenant->getKey());

        $job->handle();
        $job->handle();

        $tenant->refresh();
        $this->assertSame('ready', $tenant->provisioningStatus());
        $this->assertSame($databaseName, $tenant->getInternal('db_name'));
        $this->assertTrue(Storage::disk('tenant_private')->directoryExists($tenant->tenant_uid.'/logos'));
        $this->assertTrue(Storage::disk('tenant_private')->directoryExists($tenant->tenant_uid.'/schematics'));
        $this->assertTrue(Storage::disk('tenant_private')->directoryExists($tenant->tenant_uid.'/datasheets'));

        tenancy()->initialize($tenant);
        try {
            $this->assertSame(30, DB::connection('tenant')->table('materials')->count());
            $this->assertSame(1, DB::connection('tenant')->table('company_settings')->count());
            $this->assertTrue(Schema::connection('tenant')->hasTable('valve_models'));
        } finally {
            tenancy()->end();
        }
    }

    public function test_terminal_provisioning_failure_sets_a_safe_error_state(): void
    {
        $tenant = $this->createTenant();
        $job = new ProvisionTenantDatabase($tenant->getKey());

        $job->failed(new RuntimeException('database credentials must not be exposed'));

        $tenant->refresh();
        $this->assertSame('failed', $tenant->provisioningStatus());
        $this->assertSame('Provisioning failed. Check application logs and retry.', $tenant->provisioningError());
    }

    public function test_starter_model_and_pdf_limits_reject_usage_at_the_limit(): void
    {
        $tenant = $this->createTenant();
        $limits = app(TenantPlanLimits::class);

        expect(fn () => $limits->assertWithinLimit($tenant, 'active_valve_models', 5))
            ->toThrow(ValidationException::class, 'allows a maximum of 5 active_valve_models');
        expect(fn () => $limits->assertWithinLimit($tenant, 'monthly_pdfs', 100))
            ->toThrow(ValidationException::class, 'allows a maximum of 100 monthly_pdfs');
    }

    public function test_growth_and_pro_plans_allow_unlimited_models_and_pdfs(): void
    {
        $tenant = $this->createTenant(['plan' => 'growth']);
        $limits = app(TenantPlanLimits::class);

        $limits->assertWithinLimit($tenant, 'active_valve_models', 1000);
        $limits->assertWithinLimit($tenant, 'monthly_pdfs', 1000);

        $tenant->plan = 'pro';
        $tenant->save();
        $limits->assertWithinLimit($tenant, 'active_valve_models', 1000);
        $limits->assertWithinLimit($tenant, 'monthly_pdfs', 1000);

        $this->assertSame('pro', $tenant->fresh()->plan);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createTenant(array $attributes = []): Tenant
    {
        return Tenant::create(array_merge([
            'company_name' => 'North Plant',
            'plan' => 'starter',
            'status' => 'trial',
        ], $attributes));
    }
}
