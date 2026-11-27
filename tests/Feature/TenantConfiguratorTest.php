<?php

use App\Enums\DatasheetAccessory;
use App\Enums\DatasheetCompliance;
use App\Enums\FireSafeTestStandard;
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
        $bodyBom = $model->bomTemplates()->where('part_number', 10)->firstOrFail();
        $this->assertSame('Body / Bonnet', $bodyBom->component_group);
        $this->assertSame('Pressure-Retaining Castings', $bodyBom->part_name);
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
                    'shell_test_standard' => 'ASME B16.34',
                    'seat_test_standard' => 'ANSI FCI 70-2',
                    'material_certification' => 'EN 10204 Type 3.1',
                    'ndt_requirements' => '100% RT on butt welds / PMI',
                    'fire_safe_test_standard' => FireSafeTestStandard::Other->value,
                    'fire_safe_test_standard_other' => 'Customer fire-test specification FS-12',
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
        $this->assertSame('ASME B16.34', $snapshot['datasheet_fields']['testing_compliance']['shell_test_standard']);
        $this->assertSame('ANSI FCI 70-2', $snapshot['datasheet_fields']['testing_compliance']['seat_test_standard']);
        $this->assertSame(FireSafeTestStandard::Other->value, $snapshot['datasheet_fields']['testing_compliance']['fire_safe_test_standard']);
        $this->assertSame('Customer fire-test specification FS-12', $snapshot['datasheet_fields']['testing_compliance']['fire_safe_test_standard_other']);
        $this->assertSame([DatasheetCompliance::Iso15848->value], $snapshot['datasheet_fields']['testing_compliance']['special_compliance']);
        $this->assertSame('Gate', $snapshot['model']['valve_type']);

        $pdfHtml = view('tenant.datasheets.pdf', [
            'datasheet' => $original,
            'snapshot' => $snapshot,
        ])->render();
        $this->assertStringContainsString('FCV-204', $pdfHtml);
        $this->assertStringContainsString('Original Client', $pdfHtml);
        $this->assertStringContainsString('Valve Type / Pattern', $pdfHtml);
        $this->assertStringContainsString('Gate / Gate Ready', $pdfHtml);
        $this->assertStringContainsString('PROCESS &amp; OPERATING CONDITIONS', $pdfHtml);
        $this->assertStringContainsString('Density / Specific<br>Gravity / MW', $pdfHtml);
        $this->assertStringContainsString('950 / 0.95 / 18', $pdfHtml);
        $this->assertStringContainsString('0.3 / 1.4', $pdfHtml);
        $this->assertStringContainsString('Shut-off: 45', $pdfHtml);
        $this->assertStringContainsString('Equal Percentage', $pdfHtml);
        $this->assertStringContainsString('Bolted bonnet', $pdfHtml);
        $this->assertStringContainsString('Shell / Body Test Standard', $pdfHtml);
        $this->assertStringContainsString('Seat / Closure Test Standard', $pdfHtml);
        $this->assertStringContainsString('<tr><td class="label">Fire-safe Test Standard</td><td>Other: Customer fire-test specification FS-12</td></tr>', $pdfHtml);
        $this->assertStringContainsString('ASME B16.34', $pdfHtml);
        $this->assertStringContainsString('ANSI FCI 70-2', $pdfHtml);
        $this->assertStringContainsString('CONTROL VALVE SIZING &amp; PERFORMANCE (COMPLETE FOR MODULATING SERVICE)', $pdfHtml);
        $this->assertStringContainsString('S. No.', $pdfHtml);
        $this->assertStringContainsString('Part name', $pdfHtml);
        $this->assertStringContainsString('Material details', $pdfHtml);
        $this->assertStringContainsString('Qty / Unit', $pdfHtml);
        $this->assertStringContainsString('<td class="center">1</td>', $pdfHtml);
        $this->assertStringContainsString('<td class="label">Body</td>', $pdfHtml);
        $this->assertStringContainsString('1.000 ea', $pdfHtml);
        $this->assertStringNotContainsString('Body &amp; Bonnet', $pdfHtml);
        $this->assertStringNotContainsString('alert(1)', $pdfHtml);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $pdfHtml);
        $this->assertStringContainsString('Smart Positioner (4-20 mA HART)', $pdfHtml);
        $this->assertStringContainsString('□ Limit Switches', $pdfHtml);
        $this->assertStringContainsString('<tr><td class="label">NACE MR0175 (Sour Service)</td><td>NIL</td></tr>', $pdfHtml);
        $this->assertStringNotContainsString('API 607 (Fire Safe)</td><td>NIL', $pdfHtml);
        $this->assertStringContainsString('<tr><td class="label">ISO 15848-1 (Fugitive Emissions)</td><td>Applicable</td></tr>', $pdfHtml);
        $this->assertStringContainsString('<tr><td class="label">SIL Certified</td><td>NIL</td></tr>', $pdfHtml);
        $this->assertStringNotContainsString('□ NACE MR0175', $pdfHtml);
        $this->assertStringContainsString('<td class="center">0</td>', $pdfHtml);
        $this->assertStringContainsString('Page 1 of 2', $pdfHtml);
        $this->assertStringContainsString('Page 2 of 2', $pdfHtml);
        $this->assertStringNotContainsString('Selected Dimension Measurements', $pdfHtml);
        $this->assertStringContainsString('R. Patel', $pdfHtml);

        $legacySnapshot = $snapshot;
        unset(
            $legacySnapshot['datasheet_fields']['testing_compliance']['fire_safe_test_standard'],
            $legacySnapshot['datasheet_fields']['testing_compliance']['fire_safe_test_standard_other'],
        );
        $legacySnapshot['datasheet_fields']['testing_compliance']['special_compliance'][] = FireSafeTestStandard::LEGACY_SPECIAL_COMPLIANCE_VALUE;
        $legacyPdfHtml = view('tenant.datasheets.pdf', [
            'datasheet' => $original,
            'snapshot' => $legacySnapshot,
        ])->render();
        $this->assertStringContainsString('<tr><td class="label">Fire-safe Test Standard</td><td>API 607</td></tr>', $legacyPdfHtml);

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
            'snapshot_edits' => [
                'model' => ['is_active' => false],
            ],
        ])->assertSessionHasErrors([
            'datasheet_fields.process_conditions.flow_rate.minimum',
            'datasheet_fields.control_performance',
            'datasheet_fields.actuation.mounted_accessories.0',
            'snapshot_edits.model',
        ]);
    }

    public function test_datasheet_requires_a_custom_fire_safe_standard_when_other_is_selected(): void
    {
        [$tenant, $sales] = $this->createTenantUser('sales');
        $this->prepareTenantDatabase($tenant);
        [$model, $dimension, $bom] = $this->createReadyModel($tenant);

        $this->actingAs($sales, 'web')->post(route('tenant.datasheets.store'), [
            'client_name' => 'Custom Fire-safe Standard',
            'valve_model_id' => $model->id,
            'dimension_id' => $dimension->id,
            'bom_materials' => [[
                'bom_template_id' => $bom->id,
                'material_id' => $bom->standard_material_id,
            ]],
            'datasheet_fields' => [
                'testing_compliance' => [
                    'fire_safe_test_standard' => FireSafeTestStandard::Other->value,
                ],
            ],
        ])->assertSessionHasErrors('datasheet_fields.testing_compliance.fire_safe_test_standard_other');
    }

    public function test_new_datasheet_uses_compact_setup_and_opens_editor_without_creating_a_record(): void
    {
        Queue::fake();
        [$tenant, $sales] = $this->createTenantUser('sales');
        $this->prepareTenantDatabase($tenant);
        [$model, $dimension] = $this->createReadyModel($tenant);

        $this->actingAs($sales, 'web')
            ->get(route('tenant.datasheets.create'))
            ->assertOk()
            ->assertSee('Step 1 of 2')
            ->assertSee('name="client_name"', false)
            ->assertSee('name="project_reference"', false)
            ->assertSee('name="document_number"', false)
            ->assertSee('valve_model_id', false)
            ->assertSee('dimension_id', false)
            ->assertSee('Next')
            ->assertDontSee('datasheet_fields[process_conditions][flow_rate][minimum]', false)
            ->assertDontSee('BOM materials');

        $response = $this->actingAs($sales, 'web')->post(route('tenant.datasheets.prepare'), [
            'client_name' => 'Setup Client',
            'project_reference' => 'SETUP-204',
            'document_number' => 'TDS-SETUP',
            'valve_model_id' => $model->id,
            'dimension_id' => $dimension->id,
        ]);
        $response->assertRedirect();
        $editorUrl = $response->headers->get('Location');
        $draftId = basename((string) parse_url($editorUrl, PHP_URL_PATH));

        $this->actingAs($sales, 'web')->get($editorUrl)
            ->assertOk()
            ->assertSee('Step 2 of 2')
            ->assertSee('name="snapshot_edits[model][name]"', false)
            ->assertSee('value="Gate Ready"', false)
            ->assertSee('name="datasheet_fields[mechanical][nominal_size]"', false)
            ->assertSee('value="(4&quot;) Body"', false)
            ->assertSee('name="datasheet_fields[testing_compliance][shell_test_standard]"', false)
            ->assertSee('name="datasheet_fields[testing_compliance][seat_test_standard]"', false)
            ->assertSee('name="datasheet_fields[testing_compliance][fire_safe_test_standard]"', false)
            ->assertSee('value="API 607"', false)
            ->assertSee('value="API 6FA"', false)
            ->assertSee('value="ISO 10497"', false)
            ->assertSee('value="Other"', false)
            ->assertSee('name="datasheet_fields[testing_compliance][fire_safe_test_standard_other]"', false)
            ->assertDontSee('name="datasheet_fields[testing_compliance][hydrostatic_seat_test_standard]"', false)
            ->assertDontSee('name="snapshot_edits[dimension][size_dn]"', false)
            ->assertDontSee('name="snapshot_edits[dimension][size_inch]"', false)
            ->assertDontSee('name="datasheet_fields[mechanical][line_size]"', false)
            ->assertSee('name="snapshot_edits[bom][0][part_name]"', false)
            ->assertDontSee('name="snapshot_edits[bom][0][component_group]"', false)
            ->assertDontSee('name="snapshot_edits[bom][0][notes]"', false)
            ->assertSee('name="snapshot_edits[bom][0][quantity_and_unit]"', false)
            ->assertDontSee('name="snapshot_edits[bom][0][quantity_per_valve]"', false)
            ->assertDontSee('name="snapshot_edits[bom][0][unit]"', false)
            ->assertSee('>S. No.</td>', false)
            ->assertSee('>Part name</td>', false)
            ->assertSee('>Material details</td>', false)
            ->assertSee('>Qty / Unit</td>', false)
            ->assertSee('name="datasheet_fields[process_conditions][flow_rate][minimum]"', false)
            ->assertSee('name="client_name"', false)
            ->assertSee('Create datasheet');

        $this->actingAs($sales, 'web')
            ->post(route('tenant.datasheets.editor.back', $draftId))
            ->assertRedirect(route('tenant.datasheets.create'));
        $this->actingAs($sales, 'web')->get(route('tenant.datasheets.create'))
            ->assertOk()
            ->assertSee('value="Setup Client"', false)
            ->assertSee('value="SETUP-204"', false);

        tenancy()->initialize($tenant);
        $this->assertSame(0, GeneratedDatasheet::count());
        $this->assertSame(0, Queue::pushed(GenerateDatasheetPdfJob::class)->count());
        tenancy()->end();
    }

    public function test_datasheet_editor_displays_bom_serial_number_and_combined_quantity_unit(): void
    {
        [$tenant, $sales] = $this->createTenantUser('sales');
        $this->prepareTenantDatabase($tenant);
        [$model, $dimension, $bom] = $this->createReadyModel($tenant);
        tenancy()->initialize($tenant);
        $bom->update(['part_name' => 'Body & Bonnet']);
        tenancy()->end();

        $response = $this->actingAs($sales, 'web')->post(route('tenant.datasheets.prepare'), [
            'client_name' => 'Duplicate Label Client',
            'valve_model_id' => $model->id,
            'dimension_id' => $dimension->id,
        ]);
        $draftId = basename((string) parse_url((string) $response->headers->get('Location'), PHP_URL_PATH));

        $this->actingAs($sales, 'web')
            ->get(route('tenant.datasheets.editor', $draftId))
            ->assertOk()
            ->assertSee('type="text" name="snapshot_edits[bom][0][part_name]"', false)
            ->assertSee('value="Body &amp; Bonnet"', false)
            ->assertDontSee('name="snapshot_edits[bom][0][component_group]"', false)
            ->assertSee('name="snapshot_edits[bom][0][quantity_and_unit]"', false)
            ->assertDontSee('name="snapshot_edits[bom][0][quantity_per_valve]"', false)
            ->assertDontSee('name="snapshot_edits[bom][0][unit]"', false)
            ->assertSee('>S. No.</td>', false)
            ->assertSee('<td class="text-center">1</td>', false)
            ->assertSee('>Qty / Unit</td>', false)
            ->assertDontSee('>Quantity</td>', false)
            ->assertDontSee('>Unit</td>', false);
    }

    public function test_datasheet_editor_persists_edits_only_in_snapshot(): void
    {
        Queue::fake();
        [$tenant, $sales] = $this->createTenantUser('sales');
        $this->prepareTenantDatabase($tenant);
        [$model, $dimension, $bom] = $this->createReadyModel($tenant);

        $response = $this->actingAs($sales, 'web')->post(route('tenant.datasheets.prepare'), [
            'client_name' => 'Initial Client',
            'project_reference' => 'INITIAL-204',
            'document_number' => 'TDS-INITIAL',
            'valve_model_id' => $model->id,
            'dimension_id' => $dimension->id,
        ]);
        $draftId = basename((string) parse_url((string) $response->headers->get('Location'), PHP_URL_PATH));
        $this->actingAs($sales, 'web')->get(route('tenant.datasheets.editor', $draftId))->assertOk();

        $this->actingAs($sales, 'web')->post(route('tenant.datasheets.store'), [
            'draft_id' => $draftId,
            'client_name' => 'Edited Project Client',
            'project_reference' => 'EDITED-204',
            'document_number' => 'TDS-EDITED',
            'valve_model_id' => $model->id,
            'dimension_id' => $dimension->id,
            'bom_materials' => [[
                'bom_template_id' => $bom->id,
                'material_id' => $bom->standard_material_id,
            ]],
            'snapshot_edits' => [
                'model' => [
                    'name' => 'Project-Specific Valve',
                    'valve_category' => 'Control / Modulating',
                    'valve_type' => 'Globe',
                    'design_standard' => 'Project ASME Standard',
                ],
                'dimension' => [
                    'size_inch' => '6"',
                    'size_dn' => 'DN150',
                    'pressure_class' => 'Class 300',
                    'end_connection' => 'RF Flange',
                ],
                'bom' => [
                    [
                        'part_name' => 'Project Body',
                        'material_code' => 'PROJECT-WCB',
                        'material_name' => 'Project Carbon Steel',
                        'quantity_and_unit' => '2 set',
                        'notes' => 'Project-specific BOM note',
                    ],
                ],
            ],
            'notes' => 'Project-specific vendor notes',
            'datasheet_fields' => [
                'document_status' => 'Issued for Approval',
                'process_conditions' => [
                    'fluid_phase_state' => 'Liquid, clean water',
                    'flow_rate' => [
                        'units' => 'm³/h',
                        'minimum' => 10,
                        'normal' => 20,
                        'maximum' => 30,
                    ],
                ],
                'mechanical' => [
                    'nominal_size' => 'DN150 (6") Body / 4" Line',
                ],
                'control_performance' => [
                    'predicted_noise' => '72 dBA',
                ],
                'testing_compliance' => [
                    'shell_test_standard' => '',
                    'seat_test_standard' => '',
                    'seat_leakage_class' => '',
                ],
            ],
        ])->assertRedirect(route('tenant.datasheets.index'));

        tenancy()->initialize($tenant);
        $datasheet = GeneratedDatasheet::where('document_number', 'TDS-EDITED')->firstOrFail();
        $snapshot = $datasheet->selected_specs;

        $this->assertSame('Edited Project Client', $datasheet->client_name);
        $this->assertSame('Project-Specific Valve', $snapshot['model']['name']);
        $this->assertSame('Control / Modulating', $snapshot['model']['valve_category']);
        $this->assertSame('6"', $snapshot['dimension']['size_inch']);
        $this->assertSame('DN150 (6") Body / 4" Line', $snapshot['datasheet_fields']['mechanical']['nominal_size']);
        $this->assertSame('Body / Bonnet', $snapshot['bom'][0]['component_group']);
        $this->assertSame('Project Body', $snapshot['bom'][0]['part_name']);
        $this->assertSame('PROJECT-WCB', $snapshot['bom'][0]['material_code']);
        $this->assertSame('2', $snapshot['bom'][0]['quantity_per_valve']);
        $this->assertSame('set', $snapshot['bom'][0]['unit']);
        $this->assertSame('Liquid, clean water', $snapshot['datasheet_fields']['process_conditions']['fluid_phase_state']);
        $this->assertSame('72 dBA', $snapshot['datasheet_fields']['control_performance']['predicted_noise']);
        $this->assertNull($snapshot['datasheet_fields']['testing_compliance']['shell_test_standard']);
        $this->assertNull($snapshot['datasheet_fields']['testing_compliance']['seat_test_standard']);
        $this->assertNull($snapshot['datasheet_fields']['testing_compliance']['seat_leakage_class']);
        $this->assertSame('Gate Ready', ValveModel::findOrFail($model->id)->name);
        $this->assertSame('4"', ValveDimension::findOrFail($dimension->id)->size_inch);
        $this->assertSame('Body', $bom->fresh()->part_name);
        $pdfHtml = view('tenant.datasheets.pdf', [
            'datasheet' => $datasheet,
            'snapshot' => $snapshot,
        ])->render();
        $this->assertStringContainsString('DN150 (6&quot;) Body / 4&quot; Line', $pdfHtml);
        $this->assertStringContainsString('<td class="label">Project Body</td>', $pdfHtml);
        $this->assertStringContainsString('2 set', $pdfHtml);
        $this->assertStringNotContainsString('API 598', $pdfHtml);
        $this->assertStringNotContainsString('ANSI Class V', $pdfHtml);
        $this->assertSame(1, Queue::pushed(
            GenerateDatasheetPdfJob::class,
            fn ($job, $queue) => $queue === 'high'
                && $job->tenantId === $tenant->id
                && $job->datasheetId === $datasheet->id,
        )->count());
        tenancy()->end();
    }

    public function test_setup_rejects_a_dimension_from_another_model(): void
    {
        [$tenant, $sales] = $this->createTenantUser('sales');
        $this->prepareTenantDatabase($tenant);
        [$model] = $this->createReadyModel($tenant);

        tenancy()->initialize($tenant);
        $otherModel = ValveModel::create([
            'name' => 'Other Model',
            'valve_category' => 'Manual Isolation',
            'valve_type' => 'Ball',
            'design_standard' => 'API 6D',
            'testing_standard' => 'API 598',
            'is_active' => true,
        ]);
        $otherDimension = ValveDimension::create([
            'valve_model_id' => $otherModel->id,
            'size_inch' => '8"',
            'pressure_class' => 'Class 300',
            'end_connection' => 'RF Flange',
        ]);
        tenancy()->end();

        $this->actingAs($sales, 'web')->post(route('tenant.datasheets.prepare'), [
            'client_name' => 'Invalid Setup',
            'valve_model_id' => $model->id,
            'dimension_id' => $otherDimension->id,
        ])->assertSessionHasErrors('dimension_id');
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
