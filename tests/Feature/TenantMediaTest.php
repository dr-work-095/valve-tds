<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TenantMediaTest extends TestCase
{
    use RefreshDatabase;

    private array $tenantDatabaseFiles = [];

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
        DB::purge('tenant');
        foreach ($this->tenantDatabaseFiles as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
        parent::tearDown();
    }

    public function test_authenticated_user_can_load_only_the_initialized_tenants_media(): void
    {
        [$tenant, $user] = $this->createTenantUser();
        $this->prepareTenantDatabase($tenant);
        Storage::fake('tenant_private');
        Storage::disk('tenant_private')->put($tenant->tenant_uid.'/logos/company.txt', 'tenant-logo');

        $this->actingAs($user, 'web')
            ->get(route('tenant.media', ['category' => 'logos', 'path' => 'company.txt']))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-cache, no-store, private')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_media_route_rejects_parent_directory_traversal(): void
    {
        [$tenant, $user] = $this->createTenantUser();
        $this->prepareTenantDatabase($tenant);

        $this->actingAs($user, 'web')
            ->get('/media/logos/../secret.txt')
            ->assertNotFound();
    }

    public function test_media_route_cannot_access_another_tenants_private_file(): void
    {
        [$tenant, $user] = $this->createTenantUser();
        $this->prepareTenantDatabase($tenant);
        Storage::fake('tenant_private');
        Storage::disk('tenant_private')->put('tenant_othercompany/logos/company.txt', 'other-tenant');

        $this->actingAs($user, 'web')
            ->get(route('tenant.media', ['category' => 'logos', 'path' => 'company.txt']))
            ->assertNotFound();
    }

    private function createTenantUser(): array
    {
        $tenant = Tenant::create([
            'company_name' => 'North Plant',
            'plan' => 'starter',
            'status' => 'active',
        ]);
        $user = User::create([
            'tenant_id' => $tenant->tenant_uid,
            'name' => 'Tenant Admin',
            'email' => 'media-admin-'.$tenant->getKey().'@example.test',
            'password' => 'secure-password',
            'role' => 'admin',
            'is_active' => true,
        ]);

        return [$tenant, $user];
    }

    private function prepareTenantDatabase(Tenant $tenant): void
    {
        $databasePath = database_path($tenant->database()->getName());
        $this->tenantDatabaseFiles[] = $databasePath;
        file_put_contents($databasePath, '');

        tenancy()->initialize($tenant);
        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => database_path('migrations/tenant'),
            '--realpath' => true,
            '--force' => true,
            '--no-interaction' => true,
        ]);
        tenancy()->end();
    }
}
