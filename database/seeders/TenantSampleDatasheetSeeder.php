<?php

namespace Database\Seeders;

use App\Enums\DatasheetAccessory;
use App\Enums\DatasheetCompliance;
use App\Enums\ValveComponentGroup;
use App\Jobs\GenerateDatasheetPdfJob;
use App\Models\GeneratedDatasheet;
use App\Models\Material;
use App\Models\Tenant;
use App\Models\ValveModel;
use App\Support\DatasheetSnapshotBuilder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TenantSampleDatasheetSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('The sample datasheet seeder can only run in local or testing environments.');
        }

        $wasInitialized = tenancy()->initialized;
        $tenant = $wasInitialized
            ? tenancy()->tenant
            : Tenant::on('central')->orderBy('id')->first();

        if (! ($tenant instanceof Tenant)) {
            throw new RuntimeException('No tenant is available to receive the sample datasheet.');
        }

        try {
            if (! $wasInitialized) {
                tenancy()->initialize($tenant);
            }

            $this->seedTenant($tenant);
        } finally {
            if (! $wasInitialized && tenancy()->initialized) {
                tenancy()->end();
            }
        }
    }

    private function seedTenant(Tenant $tenant): void
    {
        $createdByUserId = $tenant->users()->orderBy('id')->value('id');
        if ($createdByUserId === null) {
            throw new RuntimeException('Create a user for this tenant before seeding the sample datasheet.');
        }

        $createdDatasheetId = null;

        DB::connection('tenant')->transaction(function () use ($createdByUserId, &$createdDatasheetId): void {
            $model = ValveModel::updateOrCreate(
                ['name' => 'Sample Globe Control Valve - FCV-204'],
                [
                    'valve_category' => 'Control / Modulating',
                    'valve_type' => 'Globe',
                    'design_standard' => 'ASME B16.34 / ISA S75.01',
                    'testing_standard' => 'ASME B16.34 / ANSI FCI 70-2',
                    'face_to_face_standard' => 'ISA S75.08.01',
                    'end_connection_standard' => 'ASME B16.5',
                    'flange_facing_finish' => 'Raised Face, 125-250 AARH',
                    'bonnet_cover_type' => 'Bolted Standard Bonnet',
                    'bore_port_configuration' => 'Reduced Port (Anti-Cavitation)',
                    'flow_characteristic' => 'Equal Percentage',
                    'seat_leakage_class' => 'ANSI Class V (Metal Seated)',
                    'custom_attributes' => [
                        'trim_design' => 'Cage-guided, multi-stage anti-cavitation',
                    ],
                    'is_active' => true,
                ],
            );

            $dimension = $model->dimensions()->updateOrCreate(
                [
                    'size_inch' => '3"',
                    'pressure_class' => 'ASME Class 300',
                    'end_connection' => 'RF Flange',
                ],
                [
                    'size_dn' => 'DN80',
                    'face_to_face_mm' => 241.30,
                    'end_to_end_mm' => 285.00,
                    'flange_od_mm' => 323.85,
                    'center_to_top_mm' => 457.00,
                    'weight_kg' => 58.20,
                    'extra_dimensions' => [
                        'line_size_dn' => 'DN100',
                        'line_size_inch' => '4"',
                    ],
                ],
            );

            $components = [
                [
                    'part_number' => 10,
                    'part_name' => 'Body & Bonnet',
                    'component_group' => ValveComponentGroup::BodyBonnet->value,
                    'material_code' => 'SAMPLE-A216-WCB',
                    'material_name' => 'ASTM A216 Gr. WCB Cast Carbon Steel',
                    'material_category' => 'Body/Bonnet',
                    'quantity' => 1,
                    'unit' => 'ea',
                    'notes' => 'Pressure-retaining cast components.',
                ],
                [
                    'part_number' => 20,
                    'part_name' => 'Stem / Shaft',
                    'component_group' => ValveComponentGroup::StemShaft->value,
                    'material_code' => 'SAMPLE-A564-630',
                    'material_name' => 'ASTM A564 Gr. 630 (17-4PH)',
                    'material_category' => 'Trim/Stem',
                    'quantity' => 1,
                    'unit' => 'ea',
                    'notes' => 'Heat-treated stainless steel.',
                ],
                [
                    'part_number' => 30,
                    'part_name' => 'Multi-Stage Anti-Cavitation Trim',
                    'component_group' => ValveComponentGroup::Trim->value,
                    'material_code' => 'SAMPLE-F316-STELLITE',
                    'material_name' => 'ASTM A182 F316 + Stellite 6',
                    'material_category' => 'Trim/Stem',
                    'quantity' => 1,
                    'unit' => 'set',
                    'notes' => 'Illustrative trim material selection.',
                ],
                [
                    'part_number' => 40,
                    'part_name' => 'Stem Packing / Seals',
                    'component_group' => ValveComponentGroup::SealsGaskets->value,
                    'material_code' => 'SAMPLE-PTFE-V-RING',
                    'material_name' => 'PTFE V-Ring, Low-Emission',
                    'material_category' => 'Seat/Seal',
                    'quantity' => 1,
                    'unit' => 'set',
                    'notes' => 'Low-emission packing set.',
                ],
                [
                    'part_number' => 50,
                    'part_name' => 'Disc / Plug',
                    'component_group' => ValveComponentGroup::DiscBallPlugWedge->value,
                    'material_code' => 'SAMPLE-F316-DISC',
                    'material_name' => 'ASTM A182 F316 + Stellite 6 Overlay',
                    'material_category' => 'Trim/Stem',
                    'quantity' => 1,
                    'unit' => 'ea',
                    'notes' => 'Hard-faced throttling element.',
                ],
                [
                    'part_number' => 60,
                    'part_name' => 'Body Gasket / O-Rings',
                    'component_group' => ValveComponentGroup::SealsGaskets->value,
                    'material_code' => 'SAMPLE-SS316-GRAPHITE-GASKET',
                    'material_name' => 'Spiral-Wound SS316 with Graphite',
                    'material_category' => 'Seat/Seal',
                    'quantity' => 1,
                    'unit' => 'set',
                    'notes' => 'Illustrative pressure-boundary gasket.',
                ],
                [
                    'part_number' => 70,
                    'part_name' => 'Seat / Seat Ring',
                    'component_group' => ValveComponentGroup::SeatSeatRing->value,
                    'material_code' => 'SAMPLE-SS316-SEAT',
                    'material_name' => 'SS316 with Stellite 6 Overlay',
                    'material_category' => 'Seat/Seal',
                    'quantity' => 1,
                    'unit' => 'ea',
                    'notes' => 'Hard-faced seat ring.',
                ],
                [
                    'part_number' => 80,
                    'part_name' => 'Bolting (Studs & Nuts)',
                    'component_group' => ValveComponentGroup::Bolting->value,
                    'material_code' => 'SAMPLE-B7-2H-BOLTING',
                    'material_name' => 'ASTM A193 B7 / A194 Gr. 2H',
                    'material_category' => 'Fasteners',
                    'quantity' => 1,
                    'unit' => 'set',
                    'notes' => 'Stud and nut set.',
                ],
            ];

            $bomMaterials = [];
            foreach ($components as $component) {
                $material = Material::updateOrCreate(
                    ['code' => $component['material_code']],
                    [
                        'name' => $component['material_name'],
                        'category' => $component['material_category'],
                        'is_active' => true,
                    ],
                );

                $bom = $model->bomTemplates()->updateOrCreate(
                    ['part_number' => $component['part_number']],
                    [
                        'part_name' => $component['part_name'],
                        'component_group' => $component['component_group'],
                        'quantity_per_valve' => $component['quantity'],
                        'unit' => $component['unit'],
                        'notes' => $component['notes'],
                        'standard_material_id' => $material->id,
                        'optional_material_ids' => [],
                    ],
                );

                $bomMaterials[] = [
                    'bom_template_id' => $bom->id,
                    'material_id' => $material->id,
                ];
            }

            $companySettings = DB::connection('tenant')
                ->table('company_settings')
                ->where('id', 1)
                ->first();

            $input = [
                'client_name' => 'Example Process Industries',
                'project_reference' => 'PRJ-409-UNIT2',
                'notes' => 'ILLUSTRATIVE SAMPLE ONLY. Verify all ratings, materials, dimensions, and operating values against the approved project design before procurement or use.',
                'datasheet_fields' => [
                    'document_status' => 'Issued for Inquiry',
                    'identification' => [
                        'equipment_tag' => 'FCV-204',
                        'pid_drawing_number' => 'PID-409-02-014',
                        'service_fluid_name' => 'Boiler Feedwater Control',
                        'piping_line_spec' => '4"-BFW-204-C3A',
                        'quantity_required' => '1 No.',
                        'installation_location' => 'Outdoor, Process Area B',
                    ],
                    'process_conditions' => [
                        'fluid_phase_state' => 'Liquid (Clean Demineralized Boiler Feedwater)',
                        'flow_rate' => [
                            'units' => 'm³/h',
                            'minimum' => 35.0,
                            'normal' => 85.0,
                            'maximum' => 115.0,
                        ],
                        'inlet_pressure' => [
                            'units' => 'bar(g)',
                            'minimum' => 42.0,
                            'normal' => 40.5,
                            'maximum' => 39.0,
                        ],
                        'outlet_pressure' => [
                            'units' => 'bar(g)',
                            'minimum' => 32.0,
                            'normal' => 33.5,
                            'maximum' => 34.0,
                            'shutoff_delta_p' => 45.0,
                        ],
                        'operating_temperature' => [
                            'units' => '°C',
                            'minimum' => 110,
                            'normal' => 135,
                            'maximum' => 145,
                        ],
                        'density' => [
                            'units' => 'kg/m³',
                            'minimum' => 951,
                            'normal' => 930,
                            'maximum' => 921,
                        ],
                        'specific_gravity' => [
                            'minimum' => 0.951,
                            'normal' => 0.930,
                            'maximum' => 0.921,
                        ],
                        'molecular_weight' => [
                            'units' => 'g/mol',
                            'minimum' => 18.015,
                            'normal' => 18.015,
                            'maximum' => 18.015,
                        ],
                        'viscosity' => [
                            'units' => 'cP',
                            'minimum' => 0.25,
                            'normal' => 0.20,
                            'maximum' => 0.19,
                        ],
                        'vapor_pressure' => [
                            'units' => 'bar(a)',
                            'minimum' => 1.43,
                            'normal' => 3.13,
                            'maximum' => 4.15,
                        ],
                        'design_pressure' => '50.0 bar(g)',
                        'design_temperature' => '180 °C',
                    ],
                    'mechanical' => [
                        'line_size' => 'DN100 (4 inch)',
                    ],
                    'control_performance' => [
                        'valve_travel' => [
                            'minimum' => 28,
                            'normal' => 62,
                            'maximum' => 79,
                        ],
                        'required_capacity' => [
                            'units' => 'Cv',
                            'minimum' => 12.6,
                            'normal' => 36.8,
                            'maximum' => 58.4,
                        ],
                        'predicted_noise' => '76 dBA @ 1 m (Limit < 85 dBA)',
                        'selected_rated_cv' => 82.0,
                        'selected_rated_kv' => 70.9,
                        'cavitation_status' => 'Incipient Cavitation Mitigated',
                    ],
                    'actuation' => [
                        'actuator_type' => 'Pneumatic Spring-Diaphragm',
                        'supply_rating' => '4.5 bar(g) Instrument Air',
                        'fail_safe_action' => 'Fail Close (FC) — Air to Open',
                        'stroking_time' => '6 sec Open / 5 sec Close',
                        'mounted_accessories' => [
                            DatasheetAccessory::SmartPositioner->value,
                            DatasheetAccessory::AirFilterRegulator->value,
                        ],
                    ],
                    'testing_compliance' => [
                        'hydrostatic_seat_test_standard' => 'ASME B16.34 / ANSI FCI 70-2',
                        'seat_leakage_class' => 'ANSI Class V (Metal Seated)',
                        'material_certification' => 'EN 10204 Type 3.1',
                        'ndt_requirements' => '100% RT on Butt Welds / PMI',
                        'special_compliance' => [
                            DatasheetCompliance::NaceMr0175->value,
                            DatasheetCompliance::Api607->value,
                            DatasheetCompliance::Iso15848->value,
                        ],
                        'other_compliance' => 'Confirm applicability of SIL certification during project engineering.',
                    ],
                    'revision' => [
                        'description' => 'Issued for Design / Inquiry',
                        'prepared_by' => 'A. Engineer',
                        'checked_by' => 'P. Reviewer',
                        'approved_by' => 'S. Approver',
                    ],
                ],
            ];

            $snapshot = app(DatasheetSnapshotBuilder::class)->build(
                $model,
                $dimension,
                $bomMaterials,
                $companySettings === null ? [] : (array) $companySettings,
                $input,
            );

            $datasheet = GeneratedDatasheet::firstOrCreate(
                [
                    'document_number' => 'SAMPLE-DS-VAL-204-A',
                    'revision' => 'Rev 0',
                ],
                [
                    'created_by_user_id' => (int) $createdByUserId,
                    'valve_model_id' => $model->id,
                    'client_name' => $input['client_name'],
                    'project_reference' => $input['project_reference'],
                    'selected_specs' => $snapshot,
                    'status' => 'queued',
                ],
            );

            if ($datasheet->wasRecentlyCreated) {
                $createdDatasheetId = (int) $datasheet->id;
            }
        });

        if ($createdDatasheetId !== null) {
            GenerateDatasheetPdfJob::dispatch((int) $tenant->getKey(), $createdDatasheetId);
        }
    }
}
