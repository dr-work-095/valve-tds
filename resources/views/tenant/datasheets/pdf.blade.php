@php
    $company = $snapshot['company'] ?? [];
    $model = $snapshot['model'] ?? [];
    $dimension = $snapshot['dimension'] ?? [];
    $bom = $snapshot['bom'] ?? [];
    $fields = $snapshot['datasheet_fields'] ?? [];
    $repeatableFieldNames = [
        'additional_process_conditions',
        'additional_mechanical_specs',
        'additional_bom_items',
        'additional_control_cases',
        'additional_accessories',
        'additional_compliance',
        'additional_quality_planning',
    ];
    $repeatableRows = [];
    foreach ($repeatableFieldNames as $repeatableFieldName) {
        $rows = $fields[$repeatableFieldName] ?? [];
        $repeatableRows[$repeatableFieldName] = is_array($rows)
            ? array_filter($rows, 'is_array')
            : [];
    }
    $identification = $fields['identification'] ?? [];
    $process = $fields['process_conditions'] ?? [];
    $mechanical = $fields['mechanical'] ?? [];
    $performance = $fields['control_performance'] ?? [];
    $qualityPlanning = $fields['quality_planning'] ?? [];
    $actuation = $fields['actuation'] ?? [];
    $testing = $fields['testing_compliance'] ?? [];
    $revision = $fields['revision'] ?? [];
    $revisionDate = $revision['date'] ?? $datasheet->created_at?->format('Y-m-d');
    $accessories = $actuation['mounted_accessories'] ?? [];
    $compliance = $testing['special_compliance'] ?? [];
    $legacyFireSafeSelected = is_array($compliance)
        && in_array(\App\Enums\FireSafeTestStandard::LEGACY_SPECIAL_COMPLIANCE_VALUE, $compliance, true);
    $fireSafeTestStandard = array_key_exists('fire_safe_test_standard', $testing)
        ? $testing['fire_safe_test_standard']
        : ($legacyFireSafeSelected ? \App\Enums\FireSafeTestStandard::Api607->value : null);
    $fireSafeOtherStandard = $testing['fire_safe_test_standard_other'] ?? null;
    $fireSafeTestStandardDisplay = $fireSafeTestStandard === \App\Enums\FireSafeTestStandard::Other->value
        ? (filled($fireSafeOtherStandard) ? $fireSafeOtherStandard : $fireSafeTestStandard)
        : $fireSafeTestStandard;
    $shellTestStandard = array_key_exists('shell_test_standard', $testing)
        ? $testing['shell_test_standard']
        : ($testing['hydrostatic_seat_test_standard'] ?? $model['testing_standard'] ?? null);
    $seatTestStandard = array_key_exists('seat_test_standard', $testing)
        ? $testing['seat_test_standard']
        : ($testing['hydrostatic_seat_test_standard'] ?? $model['testing_standard'] ?? null);
    $seatLeakageClass = array_key_exists('seat_leakage_class', $testing)
        ? $testing['seat_leakage_class']
        : ($model['seat_leakage_class'] ?? null);
    $valueOrDash = fn ($value) => filled($value) ? $value : '—';
    $metricValue = fn ($key, $point) => $valueOrDash(data_get($process, $key.'.'.$point));
    $metricUnit = fn ($key, $default = '—') => $valueOrDash(data_get($process, $key.'.units', $default));
    $maximumOutletPressure = $metricValue('outlet_pressure', 'maximum');
    if (filled(data_get($process, 'outlet_pressure.shutoff_delta_p'))) {
        $maximumOutletPressure .= ' (Shut-off: '.data_get($process, 'outlet_pressure.shutoff_delta_p').')';
    }
    $valveTypePattern = implode(' / ', array_filter([
        $model['valve_type'] ?? null,
        $model['name'] ?? null,
    ], fn ($value) => filled($value)));
    $bodySizeParts = [];
    if (filled($dimension['size_dn'] ?? null)) {
        $bodySizeParts[] = $dimension['size_dn'];
    }
    if (filled($dimension['size_inch'] ?? null)) {
        $bodySizeParts[] = '('.$dimension['size_inch'].')';
    }
    $bodyNominalSize = implode(' ', $bodySizeParts);
    $nominalSize = $mechanical['nominal_size'] ?? null;
    if (! array_key_exists('nominal_size', $mechanical)) {
        $nominalSizeParts = filled($bodyNominalSize) ? [$bodyNominalSize.' Body'] : [];
        if (filled($mechanical['line_size'] ?? null)) {
            $nominalSizeParts[] = $mechanical['line_size'].' Line';
        }
        $nominalSize = implode(' / ', $nominalSizeParts);
    }
    $accessoryOptions = array_map(
        fn ($accessory) => $accessory->value,
        \App\Enums\DatasheetAccessory::cases(),
    );
    $complianceOptions = array_map(
        fn ($requirement) => $requirement->value,
        \App\Enums\DatasheetCompliance::cases(),
    );
    $accessoryRows = array_chunk($accessoryOptions, 3);
    $revisionNumber = preg_replace('/^Rev\s*/i', '', (string) $datasheet->revision) ?? $datasheet->revision;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $datasheet->document_number }} — Industrial Valve Engineering Datasheet</title>
    <style>
        @page { size: A4 portrait; margin: 12mm 13mm 17mm 13mm; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { font-family: Arial, Helvetica, sans-serif; color: #202938; font-size: 7.35pt; line-height: 1.18; }
        .page { position: relative; width: 100%; page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .doc-header { width: 100%; border-collapse: collapse; table-layout: fixed; margin: 0 0 5px; }
        .doc-header td { border: .65px solid #344052; padding: 4px 6px; vertical-align: middle; }
        .brand { width: 55%; }
        .meta { width: 45%; }
        .brand-title { font-size: 12pt; font-weight: bold; letter-spacing: .25px; line-height: 1.35; }
        .brand-sub { margin-top: 1px; color: #4b5563; font-size: 7pt; }
        .logo { display: block; max-width: 42mm; max-height: 18mm; margin-bottom: 3px; object-fit: contain; }
        .meta-table { width: 100%; border-collapse: collapse; font-size: 7pt; }
        .meta-table td { border: 0; padding: 2px 3px; }
        .meta-table td:first-child { width: 43%; font-weight: bold; white-space: nowrap; }
        table.data { width: 100%; border-collapse: collapse; table-layout: fixed; margin: 0 0 6px; }
        table.data td, table.data th { border: .55px solid #344052; padding: 3.1px 5px; vertical-align: middle; overflow-wrap: break-word; }
        .section-title th { padding: 4px 6px !important; background: #202b3d; color: #fff; text-align: left; font-size: 8pt; font-weight: bold; letter-spacing: .15px; }
        .label { background: #f7f8fa; color: #303b4c; font-weight: bold; }
        .center { text-align: center; }
        .right { text-align: right; }
        .small { font-size: 6.9pt; }
        .subhead td { background: #f0f2f5; text-align: center; font-weight: bold; }
        table.data tr { page-break-inside: avoid; break-inside: avoid; }
        .checks { line-height: 1.55; }
        .revision { margin-top: 1px !important; }
        .revision th { background: #f0f2f5; text-align: center; font-weight: bold; }
        .muted { color: #5b6574; }
    </style>
</head>
<body>
<div class="page">
    <table class="doc-header">
        <tr>
            <td class="brand">
                @if (! empty($company['logo_path']))
                    @php
                        $logoPath = storage_path('app/tenants/'.$company['logo_path']);
                        $logoDataUri = null;
                        if (is_file($logoPath) && is_readable($logoPath)) {
                            $logoMime = mime_content_type($logoPath) ?: 'image/png';
                            $logoDataUri = 'data:'.$logoMime.';base64,'.base64_encode(file_get_contents($logoPath));
                        }
                    @endphp
                    @if ($logoDataUri)
                        <img class="logo" src="{{ $logoDataUri }}" alt="Company logo">
                    @endif
                @endif
                <div class="brand-title">INDUSTRIAL VALVE ENGINEERING<br>DATASHEET</div>
                <div class="brand-sub">Standard Specification Form (Isolation, Automated On/Off &amp; Control Valves)</div>
            </td>
            <td class="meta">
                <table class="meta-table">
                    <tr><td>Document No.:</td><td>{{ $datasheet->document_number }}</td></tr>
                    <tr><td>Client:</td><td>{{ $valueOrDash($datasheet->client_name) }}</td></tr>
                    <tr><td>Revision:</td><td>{{ $datasheet->revision }}</td></tr>
                    <tr><td>Document Status:</td><td>{{ $valueOrDash($fields['document_status'] ?? null) }}</td></tr>
                    <tr><td>Date:</td><td>{{ $valueOrDash($revisionDate) }}</td></tr>
                    <tr><td>Project Reference:</td><td>{{ $valueOrDash($snapshot['project_reference'] ?? null) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="data">
        <colgroup><col style="width:25%"><col style="width:25%"><col style="width:25%"><col style="width:25%"></colgroup>
        <tr class="section-title"><th colspan="4">A. IDENTIFICATION &amp; GENERAL DATA</th></tr>
        <tr><td class="label">Equipment Tag No.</td><td>{{ $valueOrDash($identification['equipment_tag'] ?? null) }}</td><td class="label">P&amp;ID Drawing No.</td><td>{{ $valueOrDash($identification['pid_drawing_number'] ?? null) }}</td></tr>
        <tr><td class="label">Service / Fluid Name</td><td>{{ $valueOrDash($identification['service_fluid_name'] ?? null) }}</td><td class="label">Piping Line No. / Spec</td><td>{{ $valueOrDash($identification['piping_line_spec'] ?? null) }}</td></tr>
        <tr><td class="label">Valve Category</td><td>{{ $valueOrDash($model['valve_category'] ?? null) }}</td><td class="label">Quantity Required</td><td>{{ $valueOrDash($identification['quantity_required'] ?? null) }}</td></tr>
        <tr><td class="label">Valve Type / Pattern</td><td>{{ $valueOrDash($valveTypePattern) }}</td><td class="label">Installation Location</td><td>{{ $valueOrDash($identification['installation_location'] ?? null) }}</td></tr>
    </table>

    <table class="data">
        <colgroup><col style="width:20%"><col style="width:20%"><col style="width:20%"><col style="width:20%"><col style="width:20%"></colgroup>
        <tr class="section-title"><th colspan="5">B. PROCESS &amp; OPERATING CONDITIONS</th></tr>
        <tr class="subhead"><td>Parameter</td><td>Units</td><td>Minimum</td><td>Normal</td><td>Maximum</td></tr>
        <tr><td class="label">Fluid Phase &amp; State</td><td class="center">—</td><td colspan="3">{{ $valueOrDash($process['fluid_phase_state'] ?? null) }}</td></tr>
        <tr><td class="label">Flow Rate (Volumetric<br>/ Mass)</td><td class="center">{{ $metricUnit('flow_rate', 'm³/h') }}</td><td class="center">{{ $metricValue('flow_rate', 'minimum') }}</td><td class="center">{{ $metricValue('flow_rate', 'normal') }}</td><td class="center">{{ $metricValue('flow_rate', 'maximum') }}</td></tr>
        <tr><td class="label">Inlet Pressure (P<sub>1</sub>)</td><td class="center">{{ $metricUnit('inlet_pressure', 'bar(g)') }}</td><td class="center">{{ $metricValue('inlet_pressure', 'minimum') }}</td><td class="center">{{ $metricValue('inlet_pressure', 'normal') }}</td><td class="center">{{ $metricValue('inlet_pressure', 'maximum') }}</td></tr>
        <tr><td class="label">Outlet Pressure (P<sub>2</sub>) /<br>Shut-off ΔP</td><td class="center">{{ $metricUnit('outlet_pressure', 'bar(g)') }}</td><td class="center">{{ $metricValue('outlet_pressure', 'minimum') }}</td><td class="center">{{ $metricValue('outlet_pressure', 'normal') }}</td><td class="center">{{ $maximumOutletPressure }}</td></tr>
        <tr><td class="label">Operating<br>Temperature (T<sub>1</sub>)</td><td class="center">{{ $metricUnit('operating_temperature', '°C') }}</td><td class="center">{{ $metricValue('operating_temperature', 'minimum') }}</td><td class="center">{{ $metricValue('operating_temperature', 'normal') }}</td><td class="center">{{ $metricValue('operating_temperature', 'maximum') }}</td></tr>
        <tr><td class="label">Density</td><td class="center">{{ $metricUnit('density', 'kg/m³') }}</td><td class="center">{{ $metricValue('density', 'minimum') }}</td><td class="center">{{ $metricValue('density', 'normal') }}</td><td class="center">{{ $metricValue('density', 'maximum') }}</td></tr>
        <tr><td class="label">Specific Gravity</td><td class="center">—</td><td class="center">{{ $metricValue('specific_gravity', 'minimum') }}</td><td class="center">{{ $metricValue('specific_gravity', 'normal') }}</td><td class="center">{{ $metricValue('specific_gravity', 'maximum') }}</td></tr>
        <tr><td class="label">Molecular Weight</td><td class="center">{{ $metricUnit('molecular_weight', 'g/mol') }}</td><td class="center">{{ $metricValue('molecular_weight', 'minimum') }}</td><td class="center">{{ $metricValue('molecular_weight', 'normal') }}</td><td class="center">{{ $metricValue('molecular_weight', 'maximum') }}</td></tr>
        <tr><td class="label">Viscosity</td><td class="center">{{ $metricUnit('viscosity', 'cP') }}</td><td class="center">{{ $metricValue('viscosity', 'minimum') }}</td><td class="center">{{ $metricValue('viscosity', 'normal') }}</td><td class="center">{{ $metricValue('viscosity', 'maximum') }}</td></tr>
        <tr><td class="label">Vapor Pressure (P<sub>v</sub>)</td><td class="center">{{ $metricUnit('vapor_pressure', 'bar(a)') }}</td><td class="center">{{ $metricValue('vapor_pressure', 'minimum') }}</td><td class="center">{{ $metricValue('vapor_pressure', 'normal') }}</td><td class="center">{{ $metricValue('vapor_pressure', 'maximum') }}</td></tr>
        <tr><td class="label">Design Pressure /<br>Design Temp.</td><td class="center">—</td><td colspan="3">{{ $valueOrDash($process['design_pressure'] ?? null) }} @if (filled($process['design_temperature'] ?? null)) @ {{ $process['design_temperature'] }} @endif</td></tr>
        @foreach ($repeatableRows['additional_process_conditions'] as $row)
            <tr>
                <td class="label">{{ $valueOrDash($row['parameter'] ?? null) }}</td>
                <td class="center">{{ $valueOrDash($row['units'] ?? null) }}</td>
                <td class="center">{{ $valueOrDash($row['minimum'] ?? null) }}</td>
                <td class="center">{{ $valueOrDash($row['normal'] ?? null) }}</td>
                <td class="center">{{ $valueOrDash($row['maximum'] ?? null) }}</td>
            </tr>
        @endforeach
    </table>

    <table class="data">
        <colgroup><col style="width:25%"><col style="width:25%"><col style="width:25%"><col style="width:25%"></colgroup>
        <tr class="section-title"><th colspan="4">C. VALVE BODY &amp; MECHANICAL CONSTRUCTION</th></tr>
        <tr><td class="label">Nominal Valve Size (DN / NPS)</td><td>{{ $valueOrDash($nominalSize) }}</td><td class="label">End Connections</td><td>{{ $valueOrDash($dimension['end_connection'] ?? null) }} ({{ $valueOrDash($model['end_connection_standard'] ?? null) }})</td></tr>
        <tr><td class="label">Pressure Class / Rating</td><td>{{ $valueOrDash($dimension['pressure_class'] ?? null) }}</td><td class="label">Flange Facing &amp; Finish</td><td>{{ $valueOrDash(filled($model['flange_facing_finish'] ?? null) ? $model['flange_facing_finish'] : ($mechanical['flange_facing_finish'] ?? null)) }}</td></tr>
        <tr><td class="label">Design Standard (API / ASME)</td><td>{{ $valueOrDash($model['design_standard'] ?? null) }}</td><td class="label">Bonnet / Cover Type</td><td>{{ $valueOrDash(filled($model['bonnet_cover_type'] ?? null) ? $model['bonnet_cover_type'] : ($mechanical['bonnet_cover_type'] ?? null)) }}</td></tr>
        <tr><td class="label">Face-to-Face Standard</td><td>{{ $valueOrDash($model['face_to_face_standard'] ?? null) }}</td><td class="label">Bore / Port Configuration</td><td>{{ $valueOrDash(filled($model['bore_port_configuration'] ?? null) ? $model['bore_port_configuration'] : ($mechanical['bore_port_configuration'] ?? null)) }}</td></tr>
        @foreach ($repeatableRows['additional_mechanical_specs'] as $row)
            <tr>
                <td class="label">{{ $valueOrDash($row['specification'] ?? null) }}</td>
                <td>{{ $valueOrDash($row['value'] ?? null) }}</td>
                <td class="label">Units</td>
                <td>{{ $valueOrDash($row['units'] ?? null) }}</td>
            </tr>
        @endforeach
    </table>

    <table class="data">
        <colgroup><col style="width:8%"><col style="width:22%"><col style="width:70%"></colgroup>
        <tr class="section-title"><th colspan="3">D. MATERIALS OF CONSTRUCTION (BILL OF MATERIALS)</th></tr>
        <tr class="subhead"><td>S. No.</td><td>Part name</td><td>Material details</td></tr>
        @forelse ($bom as $row)
            <tr>
                <td class="center">{{ $loop->iteration }}</td>
                <td class="label">{{ $row['part_name'] }}</td>
                <td>{{ $row['material_code'] }} — {{ $row['material_name'] }}</td>
            </tr>
        @empty
            <tr><td colspan="3" class="center">No BOM materials configured</td></tr>
        @endforelse
        @foreach ($repeatableRows['additional_bom_items'] as $row)
            <tr>
                <td class="center">{{ count($bom) + $loop->iteration }}</td>
                <td class="label">{{ $valueOrDash($row['part_name'] ?? null) }}</td>
                <td>{{ $valueOrDash($row['material_code'] ?? null) }} — {{ $valueOrDash($row['material_details'] ?? null) }}</td>
            </tr>
        @endforeach
    </table>
</div>

<div class="page">
    @if (($model['valve_category'] ?? null) === 'Control / Modulating')
        <table class="data">
            <colgroup><col style="width:25%"><col style="width:25%"><col style="width:25%"><col style="width:25%"></colgroup>
            <tr class="section-title"><th colspan="4">E. CONTROL VALVE SIZING &amp; PERFORMANCE (COMPLETE FOR MODULATING SERVICE)</th></tr>
            <tr><td class="label">Flow Characteristic</td><td>{{ $valueOrDash(filled($model['flow_characteristic'] ?? null) ? $model['flow_characteristic'] : ($performance['flow_characteristic'] ?? null)) }}</td><td class="label">Valve Travel (% Open)</td><td>Min: {{ $valueOrDash(data_get($performance, 'valve_travel.minimum')) }}@if (filled(data_get($performance, 'valve_travel.minimum')))%@endif &nbsp; Norm: {{ $valueOrDash(data_get($performance, 'valve_travel.normal')) }}@if (filled(data_get($performance, 'valve_travel.normal')))%@endif &nbsp; Max: {{ $valueOrDash(data_get($performance, 'valve_travel.maximum')) }}@if (filled(data_get($performance, 'valve_travel.maximum')))%@endif</td></tr>
            <tr><td class="label">Required C<sub>v</sub> / K<sub>v</sub><br>(Min/Norm/Max)</td><td>{{ $valueOrDash(data_get($performance, 'required_capacity.minimum')) }} / {{ $valueOrDash(data_get($performance, 'required_capacity.normal')) }} / {{ $valueOrDash(data_get($performance, 'required_capacity.maximum')) }} ({{ $valueOrDash(data_get($performance, 'required_capacity.units')) }})</td><td class="label">Predicted Noise (dBA)</td><td>{{ $valueOrDash($performance['predicted_noise'] ?? null) }}</td></tr>
            <tr><td class="label">Selected Rated C<sub>v</sub> / K<sub>v</sub></td><td>{{ $valueOrDash($performance['selected_rated_cv'] ?? null) }} C<sub>v</sub> ({{ $valueOrDash($performance['selected_rated_kv'] ?? null) }} K<sub>v</sub>)</td><td class="label">Cavitation / Flashing Status</td><td>{{ $valueOrDash($performance['cavitation_status'] ?? null) }}</td></tr>
        </table>
        @if ($repeatableRows['additional_control_cases'] !== [])
            <table class="data">
                <tr class="section-title"><th colspan="4">ADDITIONAL CONTROL SIZING CASES</th></tr>
                <tr class="subhead"><td>Case Name</td><td>Valve Travel</td><td>Required Capacity</td><td>Predicted Noise</td></tr>
                @foreach ($repeatableRows['additional_control_cases'] as $row)
                    <tr>
                        <td>{{ $valueOrDash($row['case_name'] ?? null) }}</td>
                        <td>{{ $valueOrDash($row['valve_travel'] ?? null) }}</td>
                        <td>{{ $valueOrDash($row['required_capacity'] ?? null) }}</td>
                        <td>{{ $valueOrDash($row['predicted_noise'] ?? null) }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
    @endif

    <table class="data">
        <colgroup><col style="width:25%"><col style="width:25%"><col style="width:25%"><col style="width:25%"></colgroup>
        <tr class="section-title"><th colspan="4">F. ACTUATION &amp; ACCESSORIES</th></tr>
        <tr><td class="label">Operator / Actuator Type</td><td>{{ $valueOrDash($actuation['actuator_type'] ?? null) }}</td><td class="label">Supply Air / Power Rating</td><td>{{ $valueOrDash($actuation['supply_rating'] ?? null) }}</td></tr>
        <tr><td class="label">Fail-Safe Action</td><td>{{ $valueOrDash($actuation['fail_safe_action'] ?? null) }}</td><td class="label">Stroking Time (Open/Close)</td><td>{{ $valueOrDash($actuation['stroking_time'] ?? null) }}</td></tr>
        <tr><td class="label">Mounted Accessories</td><td colspan="3" class="checks">
            @foreach ($accessoryRows as $row)
                @foreach ($row as $accessory)
                    {{ in_array($accessory, $accessories, true) ? '■' : '□' }} {{ $accessory }}@if (! $loop->last)&nbsp;&nbsp;@endif
                @endforeach
                @if (! $loop->last)<br>@endif
            @endforeach
            @foreach ($repeatableRows['additional_accessories'] as $accessory)
                <br>{{ $valueOrDash($accessory['accessory'] ?? null) }} — {{ $valueOrDash($accessory['details'] ?? null) }}
            @endforeach
        </td></tr>
    </table>

    <table class="data">
        <colgroup><col style="width:42%"><col style="width:58%"></colgroup>
        <tr class="section-title"><th colspan="2">G. TESTING, INSPECTION &amp; COMPLIANCE</th></tr>
        <tr><td class="label">Shell / Body Test Standard</td><td>{{ $valueOrDash($shellTestStandard) }}</td></tr>
        <tr><td class="label">Seat / Closure Test Standard</td><td>{{ $valueOrDash($seatTestStandard) }}</td></tr>
        <tr><td class="label">Seat Leakage Class</td><td>{{ $valueOrDash($seatLeakageClass) }}</td></tr>
        <tr><td class="label">Material Certification</td><td>{{ $valueOrDash($testing['material_certification'] ?? null) }}</td></tr>
        <tr><td class="label">NDT / NDE Requirements</td><td>{{ $valueOrDash($testing['ndt_requirements'] ?? null) }}</td></tr>
        <tr><td class="label">Fire-safe Test Standard</td><td>{{ $valueOrDash($fireSafeTestStandardDisplay) }}</td></tr>
        @foreach ($complianceOptions as $requirement)
            <tr><td class="label">{{ $requirement }}</td><td>{{ in_array($requirement, $compliance, true) ? 'Applicable' : 'NIL' }}</td></tr>
        @endforeach
        <tr><td class="label">Other Compliance</td><td>{{ $valueOrDash($testing['other_compliance'] ?? null) }}</td></tr>
        <tr><td class="label">Notes / Vendor Remarks</td><td>{{ $valueOrDash($snapshot['notes'] ?? null) }}</td></tr>
    </table>
    @if ($repeatableRows['additional_compliance'] !== [])
        <table class="data">
            <tr class="subhead"><td>Test / Compliance Item</td><td>Standard</td><td>Status / Notes</td></tr>
            @foreach ($repeatableRows['additional_compliance'] as $row)
                <tr>
                    <td class="label">{{ $valueOrDash($row['item'] ?? null) }}</td>
                    <td>{{ $valueOrDash($row['standard'] ?? null) }}</td>
                    <td>{{ $valueOrDash($row['status_notes'] ?? null) }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <table class="data">
        <colgroup><col style="width:42%"><col style="width:58%"></colgroup>
        <tr class="section-title"><th colspan="2">H. QUALITY &amp; PROJECT PLANNING</th></tr>
        <tr><td class="label">Inspection &amp; Test Plan (ITP)</td><td>{{ $valueOrDash($qualityPlanning['itp_required'] ?? null) }}</td></tr>
        <tr><td class="label">Third-party Inspection (TPI)</td><td>{{ $valueOrDash($qualityPlanning['third_party_inspection_required'] ?? null) }}</td></tr>
        <tr><td class="label">Witness / Hold Points</td><td>{{ $valueOrDash($qualityPlanning['witness_hold_points'] ?? null) }}</td></tr>
        <tr><td class="label">Required Quality Documents</td><td>{{ $valueOrDash($qualityPlanning['quality_documents_required'] ?? null) }}</td></tr>
        <tr><td class="label">Required On-site Date</td><td>{{ $valueOrDash($qualityPlanning['required_on_site_date'] ?? null) }}</td></tr>
        <tr><td class="label">Vendor Document Submission Due Date</td><td>{{ $valueOrDash($qualityPlanning['vendor_document_submission_due_date'] ?? null) }}</td></tr>
        <tr><td class="label">Inspection / FAT Target Date</td><td>{{ $valueOrDash($qualityPlanning['inspection_fat_target_date'] ?? null) }}</td></tr>
    </table>
    @if ($repeatableRows['additional_quality_planning'] !== [])
        <table class="data">
            <tr class="subhead"><td>Planning Item</td><td>Requirement / Notes</td><td>Due Date</td></tr>
            @foreach ($repeatableRows['additional_quality_planning'] as $row)
                <tr>
                    <td class="label">{{ $valueOrDash($row['item'] ?? null) }}</td>
                    <td>{{ $valueOrDash($row['requirement_notes'] ?? null) }}</td>
                    <td>{{ $valueOrDash($row['due_date'] ?? null) }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <table class="data revision">
        <colgroup><col style="width:10%"><col style="width:18%"><col style="width:24%"><col style="width:16%"><col style="width:16%"><col style="width:16%"></colgroup>
        <tr><th>Rev</th><th>Date</th><th>Revision Description</th><th>Prepared By</th><th>Checked By</th><th>Approved By</th></tr>
        <tr>
            <td class="center">{{ $revisionNumber }}</td>
            <td class="center">{{ $valueOrDash($revisionDate) }}</td>
            <td class="center">{{ $valueOrDash($revision['description'] ?? null) }}</td>
            <td class="center">{{ $valueOrDash($revision['prepared_by'] ?? null) }}</td>
            <td class="center">{{ $valueOrDash($revision['checked_by'] ?? null) }}</td>
            <td class="center">{{ $valueOrDash($revision['approved_by'] ?? null) }}</td>
        </tr>
    </table>
</div>
</body>
</html>
