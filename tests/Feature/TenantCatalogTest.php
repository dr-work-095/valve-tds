<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\Tenant;
use App\Models\User;
use App\Models\ValveBomTemplate;
use App\Models\ValveDimension;
use App\Models\ValveModel;
use App\Support\TenantPlanLimits;
use App\Support\ValveModelReadiness;
use Database\Seeders\TenantDatabaseSeeder;
use Illuminate\Database\QueryException;
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
        tenancy()->initialize($tenant);
        $model = ValveModel::create([
            'name' => 'Private Gate',
            'valve_category' => 'Manual Isolation',
            'valve_type' => 'Gate',
            'design_standard' => 'API 600',
            'testing_standard' => 'API 598',
            'is_active' => true,
        ]);
        tenancy()->end();

        $this->actingAs($sales, 'web')->get(route('tenant.settings.edit'))->assertForbidden();
        $this->actingAs($sales, 'web')->get(route('tenant.materials.index'))->assertForbidden();
        $this->actingAs($sales, 'web')->get(route('tenant.valve-models.index'))->assertForbidden();
        $this->actingAs($sales, 'web')->get(route('tenant.valve-models.show', $model))->assertForbidden();
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

    public function test_valve_model_can_be_edited_when_name_and_type_are_unchanged(): void
    {
        [$tenant, $admin] = $this->createTenantUser('admin');
        $this->prepareTenantDatabase($tenant);
        tenancy()->initialize($tenant);
        $model = ValveModel::create([
            'name' => 'Gate Valve',
            'valve_category' => 'Manual Isolation',
            'valve_type' => 'Gate',
            'design_standard' => 'API 600',
            'testing_standard' => 'API 598',
            'is_active' => true,
        ]);
        tenancy()->end();

        $this->actingAs($admin, 'web')->put(route('tenant.valve-models.update', $model), [
            'name' => 'Gate Valve',
            'valve_category' => 'Manual Isolation',
            'valve_type' => 'Gate',
            'design_standard' => 'API 600',
            'testing_standard' => 'API 598',
            'face_to_face_standard' => 'ASME B16.10',
            'end_connection_standard' => 'ASME B16.5',
            'flange_facing_finish' => 'Raised face, 125-250 AARH',
            'bonnet_cover_type' => 'Bolted bonnet',
            'bore_port_configuration' => 'Full bore',
            'flow_characteristic' => 'Equal Percentage',
            'seat_leakage_class' => 'ANSI Class V',
            'is_active' => 1,
        ])->assertRedirect(route('tenant.valve-models.index'));

        tenancy()->initialize($tenant);
        $this->assertSame('ASME B16.10', $model->fresh()->face_to_face_standard);
        $this->assertSame('ASME B16.5', $model->fresh()->end_connection_standard);
        $this->assertSame('Raised face, 125-250 AARH', $model->fresh()->flange_facing_finish);
        $this->assertSame('Bolted bonnet', $model->fresh()->bonnet_cover_type);
        $this->assertSame('Full bore', $model->fresh()->bore_port_configuration);
        $this->assertSame('Equal Percentage', $model->fresh()->flow_characteristic);
        $this->assertSame('ANSI Class V', $model->fresh()->seat_leakage_class);
        tenancy()->end();
    }

    public function test_bom_template_stores_component_group_quantity_unit_and_notes(): void
    {
        [$tenant, $admin] = $this->createTenantUser('admin');
        $this->prepareTenantDatabase($tenant);
        tenancy()->initialize($tenant);
        $model = ValveModel::create([
            'name' => 'Component Detail Valve',
            'valve_category' => 'Manual Isolation',
            'valve_type' => 'Ball',
            'design_standard' => 'API 6D',
            'testing_standard' => 'API 598',
            'is_active' => true,
        ]);
        $material = Material::where('is_active', true)->firstOrFail();
        tenancy()->end();

        $this->actingAs($admin, 'web')->post(route('tenant.valve-models.bom.store', $model), [
            'part_number' => 1,
            'part_name' => 'Body',
            'component_group' => 'Body / Bonnet',
            'quantity_per_valve' => '2.5',
            'unit' => 'ea',
            'notes' => 'Pressure-containing component',
            'standard_material_id' => $material->id,
        ])->assertRedirect(route('tenant.valve-models.edit', $model).'#bom');

        tenancy()->initialize($tenant);
        $bom = ValveBomTemplate::where('valve_model_id', $model->id)->firstOrFail();
        $this->assertSame('Body / Bonnet', $bom->component_group);
        $this->assertSame('2.500', $bom->quantity_per_valve);
        $this->assertSame('ea', $bom->unit);
        $this->assertSame('Pressure-containing component', $bom->notes);
        tenancy()->end();
    }

    public function test_bom_template_rejects_invalid_component_group_and_nonpositive_quantity(): void
    {
        [$tenant, $admin] = $this->createTenantUser('admin');
        $this->prepareTenantDatabase($tenant);
        tenancy()->initialize($tenant);
        $model = ValveModel::create([
            'name' => 'Invalid Component Detail Valve',
            'valve_category' => 'Manual Isolation',
            'valve_type' => 'Ball',
            'design_standard' => 'API 6D',
            'testing_standard' => 'API 598',
            'is_active' => true,
        ]);
        $material = Material::where('is_active', true)->firstOrFail();
        tenancy()->end();

        $this->actingAs($admin, 'web')->post(route('tenant.valve-models.bom.store', $model), [
            'part_number' => 1,
            'part_name' => 'Stem',
            'component_group' => 'Unknown group',
            'quantity_per_valve' => 0,
            'unit' => 'ea',
            'standard_material_id' => $material->id,
        ])->assertSessionHasErrors(['component_group', 'quantity_per_valve']);

        tenancy()->initialize($tenant);
        $this->assertSame(0, ValveBomTemplate::where('valve_model_id', $model->id)->count());
        tenancy()->end();
    }

    public function test_bom_template_rejects_quantity_above_database_precision(): void
    {
        [$tenant, $admin] = $this->createTenantUser('admin');
        $this->prepareTenantDatabase($tenant);
        tenancy()->initialize($tenant);
        $model = ValveModel::create([
            'name' => 'Oversized Quantity Valve',
            'valve_category' => 'Manual Isolation',
            'valve_type' => 'Ball',
            'design_standard' => 'API 6D',
            'testing_standard' => 'API 598',
            'is_active' => true,
        ]);
        $material = Material::where('is_active', true)->firstOrFail();
        tenancy()->end();

        $this->actingAs($admin, 'web')->post(route('tenant.valve-models.bom.store', $model), [
            'part_number' => 1,
            'part_name' => 'Stem',
            'component_group' => 'Stem / Shaft',
            'quantity_per_valve' => '10000000',
            'unit' => 'ea',
            'standard_material_id' => $material->id,
        ])->assertSessionHasErrors('quantity_per_valve');

        tenancy()->initialize($tenant);
        $this->assertSame(0, ValveBomTemplate::where('valve_model_id', $model->id)->count());
        tenancy()->end();
    }

    public function test_valve_model_rejects_an_existing_name_in_the_same_type(): void
    {
        [$tenant, $admin] = $this->createTenantUser('admin');
        $this->prepareTenantDatabase($tenant);
        tenancy()->initialize($tenant);
        ValveModel::create([
            'name' => 'Existing Gate',
            'valve_category' => 'Manual Isolation',
            'valve_type' => 'Gate',
            'design_standard' => 'API 600',
            'testing_standard' => 'API 598',
            'is_active' => true,
        ]);
        $model = ValveModel::create([
            'name' => 'My Gate',
            'valve_category' => 'Manual Isolation',
            'valve_type' => 'Gate',
            'design_standard' => 'API 600',
            'testing_standard' => 'API 598',
            'is_active' => true,
        ]);
        tenancy()->end();

        $this->actingAs($admin, 'web')->put(route('tenant.valve-models.update', $model), [
            'name' => 'Existing Gate',
            'valve_category' => 'Manual Isolation',
            'valve_type' => 'Gate',
            'design_standard' => 'API 600',
            'testing_standard' => 'API 598',
            'is_active' => 1,
        ])->assertSessionHasErrors('name');
    }

    public function test_valve_model_rejects_classifications_outside_the_predefined_options(): void
    {
        [$tenant, $admin] = $this->createTenantUser('admin');
        $this->prepareTenantDatabase($tenant);

        $this->actingAs($admin, 'web')->post(route('tenant.valve-models.store'), [
            'name' => 'Custom Valve',
            'valve_category' => 'Custom Function',
            'valve_type' => 'Custom Design',
            'design_standard' => 'API 600',
            'testing_standard' => 'API 598',
            'is_active' => 1,
        ])->assertSessionHasErrors(['valve_category', 'valve_type']);

        tenancy()->initialize($tenant);
        $this->assertSame(0, ValveModel::count());
        tenancy()->end();
    }

    public function test_tenant_migrations_preserve_existing_model_and_bom_records(): void
    {
        [$tenant] = $this->createTenantUser('admin');
        $databasePath = database_path($tenant->database()->getName());
        $this->tenantDatabaseFiles[] = $databasePath;
        file_put_contents($databasePath, '');

        tenancy()->initialize($tenant);
        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => database_path('migrations/tenant/2026_11_24_115000_create_valve_models_table.php'),
            '--realpath' => true,
            '--force' => true,
            '--no-interaction' => true,
        ]);
        DB::connection('tenant')->table('valve_models')->insert([
            'name' => 'Legacy Globe',
            'category' => 'Globe',
            'design_standard' => 'BS 1414',
            'testing_standard' => 'API 598',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => database_path('migrations/tenant/2026_11_26_052611_rename_valve_model_category_to_type.php'),
            '--realpath' => true,
            '--force' => true,
            '--no-interaction' => true,
        ]);

        foreach ([
            '2026_11_23_171707_create_materials_table.php',
            '2026_11_25_063000_create_valve_dimensions_and_bom_templates.php',
        ] as $migration) {
            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => database_path('migrations/tenant/'.$migration),
                '--realpath' => true,
                '--force' => true,
                '--no-interaction' => true,
            ]);
        }

        $materialId = DB::connection('tenant')->table('materials')->insertGetId([
            'code' => 'LEGACY-MATERIAL',
            'name' => 'Legacy material',
            'category' => 'Body/Bonnet',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $modelId = DB::connection('tenant')->table('valve_models')->where('name', 'Legacy Globe')->value('id');
        DB::connection('tenant')->table('valve_bom_templates')->insert([
            'valve_model_id' => $modelId,
            'part_number' => 1,
            'part_name' => 'Body',
            'standard_material_id' => $materialId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => database_path('migrations/tenant/2026_11_26_064500_add_reusable_construction_details_to_valve_models_and_bom_templates.php'),
            '--realpath' => true,
            '--force' => true,
            '--no-interaction' => true,
        ]);

        $model = ValveModel::where('name', 'Legacy Globe')->firstOrFail();
        $this->assertSame('Globe', $model->valve_type);
        $this->assertNull($model->valve_category);
        $this->assertNull($model->flange_facing_finish);
        $bom = ValveBomTemplate::where('valve_model_id', $model->id)->firstOrFail();
        $this->assertNull($bom->component_group);
        $this->assertSame('1.000', $bom->quantity_per_valve);
        $this->assertSame('ea', $bom->unit);
        tenancy()->end();
    }

    public function test_starter_tenant_cannot_create_a_sixth_active_valve_model(): void
    {
        [$tenant, $admin] = $this->createTenantUser('admin');
        $tenant->plan = 'starter';
        $tenant->save();
        app(TenantPlanLimits::class)->setLimits($tenant, [
            'active_users_limit' => null,
            'active_valve_models_limit' => 5,
            'monthly_pdfs_limit' => null,
        ]);
        $this->prepareTenantDatabase($tenant);

        foreach (range(1, 5) as $number) {
            tenancy()->initialize($tenant);
            ValveModel::create([
                'name' => 'Model '.$number,
                'valve_category' => 'Manual Isolation',
                'valve_type' => 'Gate',
                'design_standard' => 'API 600',
                'testing_standard' => 'API 598',
                'is_active' => true,
            ]);
            tenancy()->end();
        }

        $this->actingAs($admin, 'web')->post(route('tenant.valve-models.store'), [
            'name' => 'Model 6',
            'valve_category' => 'Manual Isolation',
            'valve_type' => 'Gate',
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
            'valve_category' => 'Manual Isolation',
            'valve_type' => 'Gate',
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

    public function test_dimension_duplicate_create_and_update_are_rejected(): void
    {
        [$tenant, $admin] = $this->createTenantUser('admin');
        $this->prepareTenantDatabase($tenant);
        tenancy()->initialize($tenant);
        $model = ValveModel::create([
            'name' => 'Gate 2',
            'valve_category' => 'Manual Isolation',
            'valve_type' => 'Gate',
            'design_standard' => 'API 600',
            'testing_standard' => 'API 598',
            'is_active' => true,
        ]);
        $first = ValveDimension::create([
            'valve_model_id' => $model->id,
            'size_inch' => '2"',
            'pressure_class' => 'Class 150',
            'end_connection' => 'Flanged RF',
        ]);
        tenancy()->end();

        $this->actingAs($admin, 'web')->post(route('tenant.valve-models.dimensions.store', $model), [
            'size_inch' => '2"',
            'pressure_class' => 'Class 150',
            'end_connection' => 'Flanged RF',
        ])->assertSessionHasErrors('size_inch');

        tenancy()->initialize($tenant);
        $second = ValveDimension::create([
            'valve_model_id' => $model->id,
            'size_inch' => '4"',
            'pressure_class' => 'Class 300',
            'end_connection' => 'Butt Weld',
        ]);
        tenancy()->end();

        $this->actingAs($admin, 'web')->put(route('tenant.valve-models.dimensions.update', [$model, $second]), [
            'size_inch' => '2"',
            'pressure_class' => 'Class 150',
            'end_connection' => 'Flanged RF',
        ])->assertSessionHasErrors('size_inch');

        tenancy()->initialize($tenant);
        $this->assertSame(2, ValveDimension::where('valve_model_id', $model->id)->count());
        $this->assertNotNull($first->fresh());
        tenancy()->end();
    }

    public function test_nullable_dimension_measurements_and_database_tuple_constraint_are_supported(): void
    {
        [$tenant, $admin] = $this->createTenantUser('admin');
        $this->prepareTenantDatabase($tenant);
        tenancy()->initialize($tenant);
        $model = ValveModel::create([
            'name' => 'Ball 1',
            'valve_category' => 'Manual Isolation',
            'valve_type' => 'Ball',
            'design_standard' => 'API 6D',
            'testing_standard' => 'API 598',
            'is_active' => true,
        ]);
        $dimension = ValveDimension::create([
            'valve_model_id' => $model->id,
            'size_inch' => '6"',
            'size_dn' => null,
            'pressure_class' => 'Class 600',
            'end_connection' => 'RTJ',
            'face_to_face_mm' => null,
            'extra_dimensions' => ['seat_width' => null],
        ]);
        $this->assertNull($dimension->face_to_face_mm);
        $this->assertNull($dimension->size_dn);

        $this->expectException(QueryException::class);
        ValveDimension::create([
            'valve_model_id' => $model->id,
            'size_inch' => '6"',
            'pressure_class' => 'Class 600',
            'end_connection' => 'RTJ',
        ]);
    }

    public function test_bom_order_duplicate_and_active_tenant_material_validation(): void
    {
        [$tenant, $admin] = $this->createTenantUser('admin');
        $this->prepareTenantDatabase($tenant);
        tenancy()->initialize($tenant);
        $model = ValveModel::create([
            'name' => 'Globe 1',
            'valve_category' => 'Control / Modulating',
            'valve_type' => 'Globe',
            'design_standard' => 'BS 1414',
            'testing_standard' => 'API 598',
            'is_active' => true,
        ]);
        $material = Material::where('is_active', true)->firstOrFail();
        ValveBomTemplate::create([
            'valve_model_id' => $model->id,
            'part_number' => 2,
            'part_name' => 'Stem',
            'standard_material_id' => $material->id,
        ]);
        ValveBomTemplate::create([
            'valve_model_id' => $model->id,
            'part_number' => 1,
            'part_name' => 'Body',
            'standard_material_id' => $material->id,
        ]);
        tenancy()->end();

        $this->actingAs($admin, 'web')->post(route('tenant.valve-models.bom.store', $model), [
            'part_number' => 1,
            'part_name' => 'Duplicate',
            'standard_material_id' => $material->id,
        ])->assertSessionHasErrors('part_number');

        tenancy()->initialize($tenant);
        $ordered = ValveBomTemplate::where('valve_model_id', $model->id)->orderBy('part_number')->pluck('part_number')->all();
        $this->assertSame([1, 2], $ordered);

        $inactive = Material::create([
            'code' => 'INACTIVE-1',
            'name' => 'Inactive',
            'category' => 'Trim/Stem',
            'is_active' => false,
        ]);
        tenancy()->end();

        $this->actingAs($admin, 'web')->post(route('tenant.valve-models.bom.store', $model), [
            'part_number' => 3,
            'part_name' => 'Seat',
            'standard_material_id' => $inactive->id,
        ])->assertSessionHasErrors('standard_material_id');
    }

    public function test_model_readiness_requires_active_model_dimension_and_bom(): void
    {
        [$tenant, $admin] = $this->createTenantUser('admin');
        $this->prepareTenantDatabase($tenant);
        tenancy()->initialize($tenant);
        $model = ValveModel::create([
            'name' => 'Check 1',
            'valve_category' => 'Check / Non-Return',
            'valve_type' => 'Check',
            'design_standard' => 'API 6D',
            'testing_standard' => 'API 598',
            'is_active' => true,
        ]);
        $readiness = app(ValveModelReadiness::class);

        $this->assertFalse($readiness->isReady($model));
        $this->assertSame([
            'At least one dimension row is required.',
            'At least one BOM row is required.',
        ], $readiness->missingRequirements($model));

        ValveDimension::create([
            'valve_model_id' => $model->id,
            'size_inch' => '3"',
            'pressure_class' => 'Class 150',
            'end_connection' => 'Flanged RF',
        ]);
        $material = Material::where('is_active', true)->firstOrFail();
        ValveBomTemplate::create([
            'valve_model_id' => $model->id,
            'part_number' => 1,
            'part_name' => 'Body',
            'standard_material_id' => $material->id,
        ]);

        $this->assertTrue($readiness->isReady($model));
        $this->assertTrue(app(ValveModelReadiness::class)->selectableQuery()->whereKey($model->id)->exists());

        $model->update(['valve_category' => null]);
        $this->assertContains('A valve category is required.', $readiness->missingRequirements($model->fresh()));
        $this->assertFalse($readiness->selectableQuery()->whereKey($model->id)->exists());
        tenancy()->end();
    }

    public function test_valve_model_edit_page_contains_dimension_and_bom_tabs_and_modals(): void
    {
        [$tenant, $admin] = $this->createTenantUser('admin');
        $this->prepareTenantDatabase($tenant);
        tenancy()->initialize($tenant);
        $model = ValveModel::create([
            'name' => 'Tabbed Gate',
            'valve_category' => 'Manual Isolation',
            'valve_type' => 'Gate',
            'design_standard' => 'API 600',
            'testing_standard' => 'API 598',
            'is_active' => true,
        ]);
        tenancy()->end();

        $this->actingAs($admin, 'web')
            ->get(route('tenant.valve-models.edit', $model))
            ->assertOk()
            ->assertSee('data-tab="dimensions"', false)
            ->assertSee('data-tab="bom"', false)
            ->assertSee('Valve Category')
            ->assertSee('Valve Type')
            ->assertSee('Reusable construction defaults')
            ->assertSee('Flange facing & finish')
            ->assertSee('Flow characteristic')
            ->assertSee('Manual Isolation')
            ->assertSee('Diaphragm')
            ->assertSee('id="dimension-modal"', false)
            ->assertSee('id="bom-modal"', false)
            ->assertSee('Component group')
            ->assertSee('Body / Bonnet')
            ->assertSee('Quantity per valve')
            ->assertSee('End-to-end');
    }

    public function test_admin_can_view_all_valve_details_without_edit_controls(): void
    {
        [$tenant, $admin] = $this->createTenantUser('admin');
        $this->prepareTenantDatabase($tenant);
        tenancy()->initialize($tenant);
        $standardMaterial = Material::where('is_active', true)->firstOrFail();
        $optionalMaterial = Material::create([
            'code' => 'VIEW-316',
            'name' => 'View Stainless Steel',
            'category' => 'Trim/Stem',
            'is_active' => true,
        ]);
        $model = ValveModel::create([
            'name' => 'Gate <script>alert(1)</script>',
            'valve_category' => 'Manual Isolation',
            'valve_type' => 'Gate',
            'design_standard' => 'API 600',
            'testing_standard' => 'API 598',
            'face_to_face_standard' => 'ASME B16.10',
            'end_connection_standard' => 'ASME B16.5',
            'flange_facing_finish' => 'Raised face',
            'bonnet_cover_type' => 'Bolted bonnet',
            'bore_port_configuration' => 'Full bore',
            'flow_characteristic' => 'Linear',
            'seat_leakage_class' => 'Class VI',
            'schematic_image_path' => $tenant->tenant_uid.'/schematics/gate.png',
            'custom_attributes' => ['trim_design' => 'Stainless trim'],
            'is_active' => true,
        ]);
        $model->dimensions()->create([
            'size_inch' => '4"',
            'size_dn' => 'DN100',
            'pressure_class' => 'Class 300',
            'end_connection' => 'Flanged RF',
            'face_to_face_mm' => 228.60,
            'end_to_end_mm' => 250.00,
            'flange_od_mm' => 254.00,
            'center_to_top_mm' => 320.00,
            'weight_kg' => 18.50,
            'extra_dimensions' => ['line_size_dn' => 'DN100'],
        ]);
        $model->bomTemplates()->create([
            'part_number' => 1,
            'part_name' => 'Body',
            'component_group' => 'Body / Bonnet',
            'quantity_per_valve' => 1,
            'unit' => 'ea',
            'notes' => 'Pressure-containing component',
            'standard_material_id' => $standardMaterial->id,
            'optional_material_ids' => [$optionalMaterial->id],
        ]);
        tenancy()->end();

        $this->actingAs($admin, 'web')
            ->get(route('tenant.valve-models.show', $model))
            ->assertOk()
            ->assertSee('Valve details')
            ->assertSee('API 600')
            ->assertSee('ASME B16.10')
            ->assertSee('Stainless trim')
            ->assertSee('DN100')
            ->assertSee('228.60 mm')
            ->assertSee('18.50 kg')
            ->assertSee('Body')
            ->assertSee('Pressure-containing component')
            ->assertSee('View Stainless Steel')
            ->assertSee('gate.png')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('name="valve_category"', false)
            ->assertDontSee('name="design_standard"', false)
            ->assertDontSee('Save valve model')
            ->assertSee(route('tenant.valve-models.edit', $model), false);

        $this->actingAs($admin, 'web')
            ->get(route('tenant.valve-models.data', ['start' => 0, 'length' => 10]))
            ->assertOk()
            ->assertJsonPath('data.0.show_url', route('tenant.valve-models.show', $model));

        $this->actingAs($admin, 'web')
            ->get(route('tenant.valve-models.show', 999999))
            ->assertNotFound();
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
