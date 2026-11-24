<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\Tenant;
use App\Models\User;
use App\Models\ValveModel;
use Database\Seeders\TenantDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TenantCatalogTest extends TestCase
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

    public function test_sales_user_is_forbidden_from_tenant_admin_routes(): void
    {
        [$tenant, $sales] = $this->createTenantUser('sales');
        $this->prepareTenantDatabase($tenant);

        $this->actingAs($sales, 'web')->get(route('tenant.settings.edit'))->assertForbidden();
        $this->actingAs($sales, 'web')->get(route('tenant.materials.index'))->assertForbidden();
        $this->actingAs($sales, 'web')->get(route('tenant.valve-models.index'))->assertForbidden();
    }

    public function test_company_admin_can_update_single_company_settings_record(): void
    {
        [$tenant, $admin] = $this->createTenantUser('admin');
        $this->prepareTenantDatabase($tenant);
        Storage::fake('tenant_private');

        $this->actingAs($admin, 'web')->put(route('tenant.settings.update'), [
            'company_name' => 'North Plant',
            'address' => '42 Plant Road',
            'tax_id_gst' => 'GST-100',
            'header_text' => 'Engineering',
            'footer_text' => 'Confidential',
            'default_notes' => ['Hydrostatic test', 'Visual inspection'],
            'logo' => UploadedFile::fake()->image('logo.jpg'),
        ])->assertRedirect(route('tenant.settings.edit'))->assertSessionHas('status');

        tenancy()->initialize($tenant);
        $settings = DB::connection('tenant')->table('company_settings')->where('id', 1)->first();
        $this->assertSame('North Plant', $settings->company_name);
        $this->assertSame('GST-100', $settings->tax_id_gst);
        $this->assertSame(['Hydrostatic test', 'Visual inspection'], json_decode($settings->default_notes, true));
        $this->assertNotNull($settings->logo_path);
        Storage::disk('tenant_private')->assertExists($settings->logo_path);

        $this->assertSame(1, DB::connection('tenant')->table('company_settings')->count());
        tenancy()->end();
    }

    public function test_company_admin_can_create_and_update_tenant_material(): void
    {
        [$tenant, $admin] = $this->createTenantUser('admin');
        $this->prepareTenantDatabase($tenant);

        $this->actingAs($admin, 'web')->post(route('tenant.materials.store'), [
            'code' => 'CUSTOM-316',
            'name' => 'Custom Stainless',
            'category' => 'Trim/Stem',
            'is_active' => 1,
        ])->assertRedirect(route('tenant.materials.index'));

        tenancy()->initialize($tenant);
        $material = Material::where('code', 'CUSTOM-316')->firstOrFail();
        $this->assertModelExists($material);
        tenancy()->end();

        $this->actingAs($admin, 'web')->put(route('tenant.materials.update', $material), [
            'code' => 'CUSTOM-316',
            'name' => 'Custom Stainless Revised',
            'category' => 'Seat/Seal',
            'is_active' => 0,
        ])->assertRedirect(route('tenant.materials.index'));

        tenancy()->initialize($tenant);
        $this->assertSame('Custom Stainless Revised', $material->fresh()->name);
        $this->assertFalse($material->fresh()->is_active);
        tenancy()->end();
    }

    public function test_duplicate_material_codes_are_rejected_by_validation(): void
    {
        [$tenant, $admin] = $this->createTenantUser('admin');
        $this->prepareTenantDatabase($tenant);
        tenancy()->initialize($tenant);
        Material::create([
            'code' => 'CUSTOM-316',
            'name' => 'Existing',
            'category' => 'Trim/Stem',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'web')->post(route('tenant.materials.store'), [
            'code' => 'CUSTOM-316',
            'name' => 'Duplicate',
            'category' => 'Trim/Stem',
            'is_active' => 1,
        ])->assertSessionHasErrors('code');

        tenancy()->initialize($tenant);
        $this->assertSame(1, Material::where('code', 'CUSTOM-316')->count());
        tenancy()->end();
    }

    public function test_starter_tenant_cannot_create_a_sixth_active_valve_model(): void
    {
        [$tenant, $admin] = $this->createTenantUser('admin');
        $tenant->plan = 'starter';
        $tenant->save();
        $this->prepareTenantDatabase($tenant);

        foreach (range(1, 5) as $number) {
            tenancy()->initialize($tenant);
            ValveModel::create([
                'name' => 'Model '.$number,
                'category' => 'Gate',
                'design_standard' => 'API 600',
                'testing_standard' => 'API 598',
                'is_active' => true,
            ]);
            tenancy()->end();
        }

        $this->actingAs($admin, 'web')->post(route('tenant.valve-models.store'), [
            'name' => 'Model 6',
            'category' => 'Gate',
            'design_standard' => 'API 600',
            'testing_standard' => 'API 598',
            'is_active' => 1,
        ])
            ->assertSessionHasErrors('active_valve_models');

        tenancy()->initialize($tenant);
        $this->assertSame(5, ValveModel::where('is_active', true)->count());
        tenancy()->end();
    }

    public function test_valve_model_schematic_uses_private_tenant_storage(): void
    {
        [$tenant, $admin] = $this->createTenantUser('admin');
        $this->prepareTenantDatabase($tenant);
        Storage::fake('tenant_private');

        $this->actingAs($admin, 'web')->post(route('tenant.valve-models.store'), [
            'name' => 'Gate Valve',
            'category' => 'Gate',
            'design_standard' => 'API 600',
            'testing_standard' => 'API 598',
            'is_active' => 1,
            'schematic' => UploadedFile::fake()->image('gate.png'),
        ])->assertRedirect(route('tenant.valve-models.index'));

        tenancy()->initialize($tenant);
        $model = ValveModel::where('name', 'Gate Valve')->firstOrFail();
        $this->assertStringStartsWith($tenant->tenant_uid.'/schematics/', $model->schematic_image_path);
        Storage::disk('tenant_private')->assertExists($model->schematic_image_path);
        tenancy()->end();
    }

    private function createTenantUser(string $role): array
    {
        $tenant = Tenant::create([
            'company_name' => 'North Plant',
            'plan' => 'starter',
            'status' => 'active',
        ]);
        $user = User::create([
            'tenant_id' => $tenant->tenant_uid,
            'name' => $role === 'admin' ? 'Tenant Admin' : 'Tenant Sales',
            'email' => $role.'-'.str_replace('-', '', $tenant->tenant_uid).'@example.test',
            'password' => 'secure-password',
            'role' => $role,
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
        app(TenantDatabaseSeeder::class)->run();
        tenancy()->end();
    }
}
