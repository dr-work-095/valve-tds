<?php

use App\Models\GeneratedDatasheet;
use App\Models\Material;
use App\Models\Tenant;
use App\Models\User;
use App\Models\ValveBomTemplate;
use App\Models\ValveDimension;
use App\Models\ValveModel;
use App\Support\TenantPlanLimits;
use Database\Seeders\TenantDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TenantConfiguratorTest extends TestCase
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

    public function test_dynamic_monthly_quota_blocks_at_configured_limit(): void
    {
        Queue::fake();
        [$tenant, $sales] = $this->createTenantUser('sales');
        app(TenantPlanLimits::class)->setLimits($tenant, [
            'monthly_pdfs_limit' => 2,
            'active_users_limit' => null,
            'active_valve_models_limit' => null,
        ]);
        $this->assertSame(2, app(TenantPlanLimits::class)->limit($tenant, 'monthly_pdfs'));
        $this->prepareTenantDatabase($tenant);
        [$model, $dimension, $bom] = $this->createReadyModel($tenant);

        for ($number = 1; $number <= 2; $number++) {
            $this->actingAs($sales, 'web')->post(route('tenant.datasheets.store'), [
                'client_name' => 'Client '.$number,
                'project_reference' => 'PR-'.$number,
                'valve_model_id' => $model->id,
                'dimension_id' => $dimension->id,
                'bom_materials' => [[
                    'bom_template_id' => $bom->id,
                    'material_id' => $bom->standard_material_id,
                ]],
            ])->assertRedirect(route('tenant.datasheets.index'));
        }

        $this->actingAs($sales, 'web')->post(route('tenant.datasheets.store'), [
            'client_name' => 'Blocked Client',
            'valve_model_id' => $model->id,
            'dimension_id' => $dimension->id,
            'bom_materials' => [[
                'bom_template_id' => $bom->id,
                'material_id' => $bom->standard_material_id,
            ]],
        ])->assertSessionHasErrors('monthly_pdfs');

        tenancy()->initialize($tenant);
        $this->assertSame(2, GeneratedDatasheet::count());
        tenancy()->end();
    }

    public function test_snapshot_is_immutable_and_revision_has_monotonic_unique_number(): void
    {
        Queue::fake();
        [$tenant, $sales] = $this->createTenantUser('sales');
        app(TenantPlanLimits::class)->setLimits($tenant, ['monthly_pdfs_limit' => null]);
        $this->prepareTenantDatabase($tenant);
        [$model, $dimension, $bom] = $this->createReadyModel($tenant);

        $this->actingAs($sales, 'web')->post(route('tenant.datasheets.store'), [
            'client_name' => 'Original Client',
            'project_reference' => 'TEND-1',
            'document_number' => 'TDS-TEST',
            'valve_model_id' => $model->id,
            'dimension_id' => $dimension->id,
            'bom_materials' => [[
                'bom_template_id' => $bom->id,
                'material_id' => $bom->standard_material_id,
            ]],
            'notes' => 'Original note',
        ])->assertRedirect(route('tenant.datasheets.index'));

        tenancy()->initialize($tenant);
        $original = GeneratedDatasheet::firstOrFail();
        $original->update(['status' => 'generated']);
        $snapshot = $original->selected_specs;
        $this->assertSame('Manual Isolation', $snapshot['model']['valve_category']);
        $this->assertSame('Gate', $snapshot['model']['valve_type']);
        $model->update(['name' => 'Renamed Model']);
        $bom->update(['part_name' => 'Renamed Part']);
        tenancy()->end();

        $this->actingAs($sales, 'web')->post(route('tenant.datasheets.revision', $original))
            ->assertRedirect(route('tenant.datasheets.index'));

        tenancy()->initialize($tenant);
        $revision = GeneratedDatasheet::where('revision', 'Rev 1')->firstOrFail();
        $this->assertSame('TDS-TEST', $revision->document_number);
        $this->assertSame($original->id, $revision->parent_datasheet_id);
        $this->assertSame($snapshot, $revision->selected_specs);
        $this->assertSame('Original note', $revision->selected_specs['notes']);
        tenancy()->end();
    }

    public function test_rejects_cross_model_dimension_and_inactive_material(): void
    {
        [$tenant, $sales] = $this->createTenantUser('sales');
        $this->prepareTenantDatabase($tenant);
        [$model, $dimension, $bom] = $this->createReadyModel($tenant);

        tenancy()->initialize($tenant);
        $otherModel = ValveModel::create([
            'name' => 'Other Model',
            'valve_category' => 'Control / Modulating',
            'valve_type' => 'Globe',
            'design_standard' => 'BS 1414',
            'testing_standard' => 'API 598',
            'is_active' => true,
        ]);
        $otherDimension = ValveDimension::create([
            'valve_model_id' => $otherModel->id,
            'size_inch' => '8"',
            'pressure_class' => 'Class 300',
            'end_connection' => 'RTJ',
        ]);
        $inactive = Material::create([
            'code' => 'INACTIVE-X',
            'name' => 'Inactive',
            'category' => 'Trim/Stem',
            'is_active' => false,
        ]);
        tenancy()->end();

        $this->actingAs($sales, 'web')->post(route('tenant.datasheets.store'), [
            'client_name' => 'Invalid',
            'valve_model_id' => $model->id,
            'dimension_id' => $otherDimension->id,
            'bom_materials' => [[
                'bom_template_id' => $bom->id,
                'material_id' => $inactive->id,
            ]],
        ])->assertSessionHasErrors('dimension_id');
    }

    public function test_create_datasheet_page_renders(): void
    {
        [$tenant, $sales] = $this->createTenantUser('sales');
        $this->prepareTenantDatabase($tenant);

        $this->actingAs($sales, 'web')
            ->get(route('tenant.datasheets.create'))
            ->assertOk()
            ->assertSee('Create datasheet')
            ->assertSee('valve_model_id', false);
    }

    private function createTenantUser(string $role): array
    {
        $tenant = Tenant::create([
            'company_name' => 'Configurator Plant',
            'plan' => 'starter',
            'status' => 'active',
        ]);
        $user = User::create([
            'tenant_id' => $tenant->tenant_uid,
            'name' => 'Tenant '.$role,
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

    private function createReadyModel(Tenant $tenant): array
    {
        tenancy()->initialize($tenant);
        $model = ValveModel::create([
            'name' => 'Gate Ready',
            'valve_category' => 'Manual Isolation',
            'valve_type' => 'Gate',
            'design_standard' => 'API 600',
            'testing_standard' => 'API 598',
            'is_active' => true,
        ]);
        $dimension = ValveDimension::create([
            'valve_model_id' => $model->id,
            'size_inch' => '4"',
            'pressure_class' => 'Class 150',
            'end_connection' => 'Flanged RF',
            'weight_kg' => 12.50,
        ]);
        $material = Material::where('is_active', true)->firstOrFail();
        $bom = ValveBomTemplate::create([
            'valve_model_id' => $model->id,
            'part_number' => 1,
            'part_name' => 'Body',
            'standard_material_id' => $material->id,
        ]);
        tenancy()->end();

        return [$model, $dimension, $bom];
    }
}
