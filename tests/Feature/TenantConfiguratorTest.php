<?php

use App\Enums\DatasheetAccessory;
use App\Enums\DatasheetCompliance;
use App\Jobs\GenerateDatasheetPdfJob;
use App\Models\GeneratedDatasheet;
use App\Models\Material;
use App\Models\Tenant;
use App\Models\User;
use App\Models\ValveBomTemplate;
use App\Models\ValveDimension;
use App\Models\ValveModel;
use App\Support\TenantPlanLimits;
use Database\Seeders\TenantDatabaseSeeder;
use Database\Seeders\TenantSampleDatasheetSeeder;
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

    public function test_sample_datasheet_seeder_creates_complete_tenant_data_once(): void
    {
        Queue::fake();
        [$tenant] = $this->createTenantUser('sales');
        [$otherTenant] = $this->createTenantUser('sales');
        $this->prepareTenantDatabase($tenant);
        $this->prepareTenantDatabase($otherTenant);

        Artisan::call('app:seed-sample-datasheet');
        Artisan::call('app:seed-sample-datasheet');

        $this->assertFalse(tenancy()->initialized);
        tenancy()->initialize($otherTenant);
        $this->assertSame(0, GeneratedDatasheet::where('document_number', 'SAMPLE-DS-VAL-204-A')->count());
        tenancy()->end();

        tenancy()->initialize($tenant);
        $model = ValveModel::where('name', 'Sample Globe Control Valve - FCV-204')->firstOrFail();
        $datasheet = GeneratedDatasheet::where('document_number', 'SAMPLE-DS-VAL-204-A')->firstOrFail();
        $snapshot = $datasheet->selected_specs;

        $this->assertSame(1, ValveModel::where('name', 'Sample Globe Control Valve - FCV-204')->count());
        $this->assertSame(1, $model->dimensions()->count());
        $this->assertSame(8, $model->bomTemplates()->count());
        $this->assertSame('Control / Modulating', $snapshot['model']['valve_category']);
        $this->assertSame('Globe', $snapshot['model']['valve_type']);
        $this->assertSame('PRJ-409-UNIT2', $snapshot['project_reference']);
        $this->assertSame('FCV-204', $snapshot['datasheet_fields']['identification']['equipment_tag']);
        $this->assertSame(8, count($snapshot['bom']));
        $this->assertSame('Issued for Inquiry', $snapshot['datasheet_fields']['document_status']);
        $this->assertSame(1, Queue::pushed(
            GenerateDatasheetPdfJob::class,
            fn ($job, $queue) => $queue === 'high'
                && $job->tenantId === $tenant->id
                && $job->datasheetId === $datasheet->id,
        )->count());
        tenancy()->end();
    }

    public function test_sample_datasheet_seeder_refuses_production_environment(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The sample datasheet seeder can only run in local or testing environments.');

        (new TenantSampleDatasheetSeeder)->run();
    }

    public function test_snapshot_is_immutable_and_revision_has_monotonic_unique_number(): void
    {
        Queue::fake();
        [$tenant, $sales] = $this->createTenantUser('sales');
        app(TenantPlanLimits::class)->setLimits($tenant, ['monthly_pdfs_limit' => null]);
        $this->prepareTenantDatabase($tenant);
        [$model, $dimension, $bom] = $this->createReadyModel($tenant);
        tenancy()->initialize($tenant);
        $model->update(['valve_category' => 'Control / Modulating']);
        $bom->update(['notes' => '<script>alert(1)</script>']);
        tenancy()->end();

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
            'datasheet_fields' => [
                'document_status' => 'Issued for Inquiry',
                'identification' => [
                    'equipment_tag' => 'FCV-204',
                    'pid_drawing_number' => 'PID-409-02-014',
                    'service_fluid_name' => 'Boiler feedwater',
                    'piping_line_spec' => '4-BFW-204-C3A',
                    'quantity_required' => '1 No.',
                    'installation_location' => 'Outdoor, Process Area B',
                ],
                'process_conditions' => [
                    'fluid_phase_state' => 'Liquid',
                    'flow_rate' => [
                        'units' => 'm3/h',
                        'minimum' => 35,
                        'normal' => 85,
                        'maximum' => 115,
                    ],
                    'outlet_pressure' => [
                        'units' => 'bar(g)',
                        'minimum' => 32,
                        'normal' => 33.5,
                        'maximum' => 34,
                        'shutoff_delta_p' => 45,
                    ],
                    'density' => [
                        'units' => 'kg/m3',
                        'minimum' => 950,
                        'normal' => 930,
                        'maximum' => 910,
                    ],
                    'specific_gravity' => [
                        'minimum' => 0.95,
                        'normal' => 0.93,
                        'maximum' => 0.91,
                    ],
                    'molecular_weight' => [
                        'units' => 'g/mol',
                        'minimum' => 18,
                        'normal' => 18,
                        'maximum' => 18,
                    ],
                    'viscosity' => [
                        'units' => 'cP',
                        'minimum' => 0.3,
                        'normal' => 0.2,
                        'maximum' => 0.1,
                    ],
                    'vapor_pressure' => [
                        'units' => 'bar(a)',
                        'minimum' => 1.4,
                        'normal' => 3.1,
                        'maximum' => 4.1,
                    ],
                    'design_pressure' => '50 bar(g)',
                    'design_temperature' => '180 C',
                ],
                'mechanical' => [
                    'line_size' => 'DN100 (4 inch)',
                ],
                'control_performance' => [
                    'valve_travel' => ['minimum' => 28, 'normal' => 62, 'maximum' => 79],
                    'required_capacity' => [
                        'units' => 'Cv',
                        'minimum' => 12.6,
                        'normal' => 36.8,
                        'maximum' => 58.4,
                    ],
                    'predicted_noise' => '76 dBA @ 1 m',
                    'selected_rated_cv' => 82,
                    'selected_rated_kv' => 70.9,
                    'cavitation_status' => 'Incipient cavitation mitigated',
                ],
                'actuation' => [
                    'actuator_type' => 'Pneumatic spring-diaphragm',
                    'supply_rating' => '4.5 bar(g) instrument air',
                    'fail_safe_action' => 'Fail close — air to open',
                    'stroking_time' => '6 sec open / 5 sec close',
                    'mounted_accessories' => [DatasheetAccessory::SmartPositioner->value],
                ],
                'testing_compliance' => [
                    'hydrostatic_seat_test_standard' => 'ASME B16.34 / ANSI FCI 70-2',
                    'material_certification' => 'EN 10204 Type 3.1',
                    'ndt_requirements' => '100% RT on butt welds / PMI',
                    'special_compliance' => [DatasheetCompliance::Iso15848->value],
                    'other_compliance' => '',
                ],
                'revision' => [
                    'description' => 'Issued for Design / Inquiry',
                    'prepared_by' => 'R. Patel',
                    'checked_by' => 'S. Mehta',
                    'approved_by' => 'A. Kulkarni',
                ],
            ],
        ])->assertRedirect(route('tenant.datasheets.index'));

        tenancy()->initialize($tenant);
        $original = GeneratedDatasheet::firstOrFail();
        $original->update(['status' => 'generated']);
        $snapshot = $original->selected_specs;
        $this->assertSame('Control / Modulating', $snapshot['model']['valve_category']);
        $this->assertSame('Raised face', $snapshot['model']['flange_facing_finish']);
        $this->assertSame('Bolted bonnet', $snapshot['model']['bonnet_cover_type']);
        $this->assertSame('Reduced port', $snapshot['model']['bore_port_configuration']);
        $this->assertSame('Equal Percentage', $snapshot['model']['flow_characteristic']);
        $this->assertSame('ANSI Class V', $snapshot['model']['seat_leakage_class']);
        $this->assertSame('FCV-204', $snapshot['datasheet_fields']['identification']['equipment_tag']);
        $this->assertSame(85, $snapshot['datasheet_fields']['process_conditions']['flow_rate']['normal']);
        $this->assertSame('Body / Bonnet', $snapshot['bom'][0]['component_group']);
        $this->assertSame('1.000', $snapshot['bom'][0]['quantity_per_valve']);
        $this->assertSame('ea', $snapshot['bom'][0]['unit']);
        $this->assertSame('<script>alert(1)</script>', $snapshot['bom'][0]['notes']);
        $this->assertSame('ASTM A216 WCB', $snapshot['bom'][0]['material_code']);
        $this->assertSame([DatasheetAccessory::SmartPositioner->value], $snapshot['datasheet_fields']['actuation']['mounted_accessories']);
        $this->assertSame([DatasheetCompliance::Iso15848->value], $snapshot['datasheet_fields']['testing_compliance']['special_compliance']);
        $this->assertSame('Gate', $snapshot['model']['valve_type']);

        $pdfHtml = view('tenant.datasheets.pdf', [
            'datasheet' => $original,
            'snapshot' => $snapshot,
        ])->render();
        $this->assertStringContainsString('FCV-204', $pdfHtml);
        $this->assertStringContainsString('Valve Type / Pattern', $pdfHtml);
        $this->assertStringContainsString('Gate / Gate Ready', $pdfHtml);
        $this->assertStringContainsString('PROCESS &amp; OPERATING CONDITIONS', $pdfHtml);
        $this->assertStringContainsString('Density / Specific<br>Gravity / MW', $pdfHtml);
        $this->assertStringContainsString('950 / 0.95 / 18', $pdfHtml);
        $this->assertStringContainsString('0.3 / 1.4', $pdfHtml);
        $this->assertStringContainsString('Shut-off: 45', $pdfHtml);
        $this->assertStringContainsString('Equal Percentage', $pdfHtml);
        $this->assertStringContainsString('Bolted bonnet', $pdfHtml);
        $this->assertStringContainsString('CONTROL VALVE SIZING &amp; PERFORMANCE (COMPLETE FOR MODULATING SERVICE)', $pdfHtml);
        $this->assertStringContainsString('Qty: 1.000 ea', $pdfHtml);
        $this->assertStringContainsString('Body &amp; Bonnet', $pdfHtml);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $pdfHtml);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $pdfHtml);
        $this->assertStringContainsString('Smart Positioner (4-20 mA HART)', $pdfHtml);
        $this->assertStringContainsString('□ Limit Switches', $pdfHtml);
        $this->assertStringContainsString('□ NACE MR0175 (Sour Service)', $pdfHtml);
        $this->assertStringContainsString('<td class="center">0</td>', $pdfHtml);
        $this->assertStringContainsString('Page 1 of 2', $pdfHtml);
        $this->assertStringContainsString('Page 2 of 2', $pdfHtml);
        $this->assertStringNotContainsString('Selected Dimension Measurements', $pdfHtml);
        $this->assertStringContainsString('R. Patel', $pdfHtml);
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

    public function test_datasheet_rejects_invalid_process_values_and_unknown_accessories(): void
    {
        [$tenant, $sales] = $this->createTenantUser('sales');
        $this->prepareTenantDatabase($tenant);
        [$model, $dimension, $bom] = $this->createReadyModel($tenant);

        $this->actingAs($sales, 'web')->post(route('tenant.datasheets.store'), [
            'client_name' => 'Invalid Engineering Inputs',
            'valve_model_id' => $model->id,
            'dimension_id' => $dimension->id,
            'bom_materials' => [[
                'bom_template_id' => $bom->id,
                'material_id' => $bom->standard_material_id,
            ]],
            'datasheet_fields' => [
                'process_conditions' => [
                    'flow_rate' => ['minimum' => 'not-a-number'],
                ],
                'control_performance' => ['predicted_noise' => '76 dBA'],
                'actuation' => [
                    'mounted_accessories' => ['Unknown accessory'],
                ],
            ],
        ])->assertSessionHasErrors([
            'datasheet_fields.process_conditions.flow_rate.minimum',
            'datasheet_fields.control_performance',
            'datasheet_fields.actuation.mounted_accessories.0',
        ]);
    }

    public function test_create_datasheet_page_renders(): void
    {
        [$tenant, $sales] = $this->createTenantUser('sales');
        $this->prepareTenantDatabase($tenant);

        $this->actingAs($sales, 'web')
            ->get(route('tenant.datasheets.create'))
            ->assertOk()
            ->assertSee('Create datasheet')
            ->assertSee('valve_model_id', false)
            ->assertSee('datasheet_fields[identification][equipment_tag]', false)
            ->assertSee('datasheet_fields[process_conditions][flow_rate][minimum]', false)
            ->assertDontSee('datasheet_fields[control_performance][flow_characteristic]', false)
            ->assertSee('datasheet_fields[testing_compliance][seat_leakage_class]', false)
            ->assertSee('NACE MR0175 (Sour Service)');
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
            'flange_facing_finish' => 'Raised face',
            'bonnet_cover_type' => 'Bolted bonnet',
            'bore_port_configuration' => 'Reduced port',
            'flow_characteristic' => 'Equal Percentage',
            'seat_leakage_class' => 'ANSI Class V',
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
            'component_group' => 'Body / Bonnet',
            'quantity_per_valve' => 1,
            'unit' => 'ea',
            'notes' => 'Standard body material',
            'standard_material_id' => $material->id,
        ]);
        tenancy()->end();

        return [$model, $dimension, $bom];
    }
}
