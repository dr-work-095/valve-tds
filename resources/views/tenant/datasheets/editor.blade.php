@php
    $company = $snapshot['company'] ?? [];
    $model = $snapshot['model'] ?? [];
    $dimension = $snapshot['dimension'] ?? [];
    $bom = $snapshot['bom'] ?? [];
    $fields = $snapshot['datasheet_fields'] ?? [];
    $identification = $fields['identification'] ?? [];
    $process = $fields['process_conditions'] ?? [];
    $mechanical = $fields['mechanical'] ?? [];
    $performance = $fields['control_performance'] ?? [];
    $actuation = $fields['actuation'] ?? [];
    $testing = $fields['testing_compliance'] ?? [];
    $revision = $fields['revision'] ?? [];
    $accessories = old('datasheet_fields.actuation.mounted_accessories', $actuation['mounted_accessories'] ?? []);
    $compliance = old('datasheet_fields.testing_compliance.special_compliance', $testing['special_compliance'] ?? []);
    $category = old('snapshot_edits.model.valve_category', $model['valve_category'] ?? '');
    $bomRows = array_chunk($bom, 2, true);
    $input = function (
        string $section,
        string $path,
        mixed $value = null,
        string $type = 'text',
        string $classes = '',
    ): \Illuminate\Support\HtmlString {
        $name = $section.'['.str_replace('.', '][', $path).']';
        $currentValue = old($section.'.'.$path, $value);
        $ariaLabel = \Illuminate\Support\Str::headline(str_replace('.', ' ', $path));
        $step = $type === 'number' ? ' step="any"' : '';
        $attributes = $type === 'hidden'
            ? ''
            : ' aria-label="'.e($ariaLabel).'" class="sheet-input '.e($classes).'"';

        return new \Illuminate\Support\HtmlString(
            '<input type="'.e($type).'" name="'.e($name).'" value="'.e($currentValue).'"'.$step.$attributes.'>',
        );
    };
    $topInput = fn (string $name, mixed $value = null, string $classes = ''): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString(
        '<input type="text" name="'.e($name).'" value="'.e(old($name, $value)).'" aria-label="'.e(\Illuminate\Support\Str::headline($name)).'" class="sheet-input '.e($classes).'">',
    );
    $snapshotInput = fn (string $path, mixed $value = null, string $type = 'text', string $classes = ''): \Illuminate\Support\HtmlString => $input('snapshot_edits', $path, $value, $type, $classes);
    $fieldInput = fn (string $path, mixed $value = null, string $type = 'text', string $classes = ''): \Illuminate\Support\HtmlString => $input('datasheet_fields', $path, $value, $type, $classes);
    $normalizeBomLabel = fn (mixed $value): string => preg_replace(
        '/[^a-z0-9]/',
        '',
        \Illuminate\Support\Str::lower((string) $value),
    ) ?? '';
    $metricValue = fn (string $key, string $point) => data_get($process, $key.'.'.$point);
    $metricUnit = fn (string $key, string $default = '') => data_get($process, $key.'.units', $default);
    $nominalSizeParts = [];
    if (filled($dimension['size_dn'] ?? null)) {
        $nominalSizeParts[] = $dimension['size_dn'];
    }
    if (filled($dimension['size_inch'] ?? null)) {
        $nominalSizeParts[] = '('.$dimension['size_inch'].')';
    }
    $bodyNominalSize = implode(' ', $nominalSizeParts);
    $nominalSizeParts = filled($bodyNominalSize) ? [$bodyNominalSize.' Body'] : [];
    if (filled($mechanical['line_size'] ?? null)) {
        $nominalSizeParts[] = $mechanical['line_size'].' Line';
    }
    $nominalSize = array_key_exists('nominal_size', $mechanical)
        ? $mechanical['nominal_size']
        : implode(' / ', $nominalSizeParts);
    $maximumOutletPressure = data_get($process, 'outlet_pressure.maximum');
    $revisionNumber = '0';
@endphp

<x-layouts.dashboard :page-title="'Edit datasheet'" :admin-mode="false" :tenant="$tenant" :user="$user">
    @push('styles')
        <style>
            .datasheet-scroll { overflow-x: auto; }
            .datasheet-page { min-width: 760px; }
            .datasheet-table { width: 100%; border-collapse: collapse; table-layout: fixed; margin: 0 0 8px; }
            .datasheet-table td, .datasheet-table th { border: 1px solid #344052; padding: 3px 4px; vertical-align: middle; overflow-wrap: anywhere; }
            .datasheet-title th { padding: 5px 6px; background: #202b3d; color: #fff; text-align: left; font-size: 11px; }
            .datasheet-label { background: #f7f8fa; color: #303b4c; font-weight: 600; }
            .datasheet-subhead td { background: #f0f2f5; text-align: center; font-weight: 600; }
            .sheet-input { display: inline-block; width: 100%; min-width: 0; border: 1px solid #cbd5e1; border-radius: 3px; background: #fff; padding: 2px 3px; color: #202938; font: inherit; line-height: 1.25; }
            .sheet-input:focus { border-color: #2563eb; outline: 2px solid rgb(37 99 235 / 20%); }
            .sheet-number { width: 2.5rem; text-align: center; }
            .sheet-unit { min-width: 3rem; text-align: center; }
            .inline-fields { display: flex; align-items: center; gap: 3px; min-width: 0; white-space: nowrap; }
            .sheet-input.sheet-inline { width: auto; flex: 0 0 auto; }
            .sheet-input.sheet-number.sheet-inline { width: 2.5rem; }
            .sheet-input.sheet-short { width: 3rem; }
            .sheet-input.sheet-medium { width: 6rem; }
            .sheet-input.sheet-line-size { width: 5rem; }
            .sheet-input.sheet-unit { width: 3.5rem; }
            .sheet-input.sheet-flex { width: 1%; min-width: 2.5rem; flex: 1 1 auto; }
            .sheet-input.sheet-inline.sheet-flex { width: 1%; flex: 1 1 auto; }
            .datasheet-page-card { max-width: 1100px; }
            @media (max-width: 767px) {
                .datasheet-page-card { padding: 10px; }
                .datasheet-page { min-width: 760px; }
            }
        </style>
    @endpush

    <div class="p-4 sm:p-6 lg:p-8">
        <div class="mx-auto max-w-6xl">
            @if ($errors->any())
                <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                    <p class="mb-1 font-semibold">Please correct the highlighted datasheet values.</p>
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="datasheet-editor" method="POST" action="{{ route('tenant.datasheets.store') }}">
                @csrf
                <input type="hidden" name="draft_id" value="{{ $draft }}">
                <input type="hidden" name="valve_model_id" value="{{ $selection['valve_model_id'] }}">
                <input type="hidden" name="dimension_id" value="{{ $selection['dimension_id'] }}">
                @foreach ($bomSelections as $index => $bomSelection)
                    <input type="hidden" name="bom_materials[{{ $index }}][bom_template_id]" value="{{ $bomSelection['bom_template_id'] }}">
                    <input type="hidden" name="bom_materials[{{ $index }}][material_id]" value="{{ $bomSelection['material_id'] }}">
                @endforeach

                <div class="sticky top-0 z-10 mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white/95 p-3 shadow-sm backdrop-blur dark:border-slate-700 dark:bg-slate-800/95">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">Step 2 of 2</p>
                        <h1 class="text-lg font-semibold text-slate-900 dark:text-white">Review and edit datasheet</h1>
                    </div>
                    <div class="flex items-center gap-2">
                        <button form="back-to-datasheet-setup" type="submit" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Back</button>
                        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Create datasheet</button>
                    </div>
                </div>

                <div class="grid gap-5">
                    <section class="datasheet-page-card rounded-xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-700 dark:bg-white sm:p-5">
                        <div class="datasheet-scroll">
                            <div class="datasheet-page text-[10px] leading-tight text-slate-800">
                                <table class="datasheet-table">
                                    <tr>
                                        <td class="w-[55%]">
                                            <div class="text-base font-bold tracking-wide">INDUSTRIAL VALVE ENGINEERING<br>DATASHEET</div>
                                            <div class="mt-1 text-[9px] text-slate-600">{{ $company['company_name'] ?? 'Standard Specification Form (Isolation, Automated On/Off & Control Valves)' }}</div>
                                        </td>
                                        <td class="w-[45%]">
                                            <table class="w-full border-collapse text-[9px]">
                                                <tr><td class="w-[36%] border-0 font-semibold">Document No.:</td><td class="border-0">{{ $topInput('document_number', $selection['document_number'] ?? '') }}</td></tr>
                                                <tr><td class="border-0 font-semibold">Client:</td><td class="border-0">{{ $topInput('client_name', $selection['client_name']) }}</td></tr>
                                                <tr><td class="border-0 font-semibold">Revision / Status:</td><td class="border-0"><div class="inline-fields">Rev 0 — {{ $fieldInput('document_status', $fields['document_status'] ?? 'Issued for Inquiry', 'text', 'sheet-inline sheet-flex') }}</div></td></tr>
                                                <tr><td class="border-0 font-semibold">Date / Project Ref.:</td><td class="border-0"><div class="inline-fields">{{ $revisionDate }} / {{ $topInput('project_reference', $selection['project_reference'] ?? '', 'sheet-inline sheet-flex') }}</div></td></tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>

                                <table class="datasheet-table">
                                    <colgroup><col style="width:25%"><col style="width:25%"><col style="width:25%"><col style="width:25%"></colgroup>
                                    <tr class="datasheet-title"><th colspan="4">A. IDENTIFICATION &amp; GENERAL DATA</th></tr>
                                    <tr><td class="datasheet-label">Equipment Tag No.</td><td>{{ $fieldInput('identification.equipment_tag', $identification['equipment_tag'] ?? '') }}</td><td class="datasheet-label">P&amp;ID Drawing No.</td><td>{{ $fieldInput('identification.pid_drawing_number', $identification['pid_drawing_number'] ?? '') }}</td></tr>
                                    <tr><td class="datasheet-label">Service / Fluid Name</td><td>{{ $fieldInput('identification.service_fluid_name', $identification['service_fluid_name'] ?? '') }}</td><td class="datasheet-label">Piping Line No. / Spec</td><td>{{ $fieldInput('identification.piping_line_spec', $identification['piping_line_spec'] ?? '') }}</td></tr>
                                    <tr>
                                        <td class="datasheet-label">Valve Category</td>
                                        <td>
                                            <select id="snapshot-valve-category" name="snapshot_edits[model][valve_category]" class="sheet-input">
                                                @foreach ($valveCategories as $valveCategory)
                                                    <option value="{{ $valveCategory->value }}" @selected($category === $valveCategory->value)>{{ $valveCategory->value }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="datasheet-label">Quantity Required</td>
                                        <td>{{ $fieldInput('identification.quantity_required', $identification['quantity_required'] ?? '') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="datasheet-label">Valve Type / Pattern</td>
                                        <td>
                                            <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-1">
                                                <select name="snapshot_edits[model][valve_type]" aria-label="Valve type" class="sheet-input">
                                                    @foreach ($valveTypes as $valveType)
                                                        <option value="{{ $valveType->value }}" @selected(old('snapshot_edits.model.valve_type', $model['valve_type'] ?? '') === $valveType->value)>{{ $valveType->value }}</option>
                                                    @endforeach
                                                </select>
                                                <span>/</span>
                                                {{ $snapshotInput('model.name', $model['name'] ?? '') }}
                                            </div>
                                        </td>
                                        <td class="datasheet-label">Installation Location</td>
                                        <td>{{ $fieldInput('identification.installation_location', $identification['installation_location'] ?? '') }}</td>
                                    </tr>
                                </table>

                                <table class="datasheet-table">
                                    <colgroup><col style="width:20%"><col style="width:20%"><col style="width:20%"><col style="width:20%"><col style="width:20%"></colgroup>
                                    <tr class="datasheet-title"><th colspan="5">B. PROCESS &amp; OPERATING CONDITIONS</th></tr>
                                    <tr class="datasheet-subhead"><td>Parameter</td><td>Units</td><td>Minimum</td><td>Normal</td><td>Maximum</td></tr>
                                    <tr><td class="datasheet-label">Fluid Phase &amp; State</td><td class="text-center">—</td><td colspan="3">{{ $fieldInput('process_conditions.fluid_phase_state', $process['fluid_phase_state'] ?? '') }}</td></tr>
                                    @foreach ([
                                        'flow_rate' => ['Flow Rate (Volumetric / Mass)', 'm³/h'],
                                        'inlet_pressure' => ['Inlet Pressure (P1)', 'bar(g)'],
                                        'outlet_pressure' => ['Outlet Pressure (P2) / Shut-off ΔP', 'bar(g)'],
                                        'operating_temperature' => ['Operating Temperature (T1)', '°C'],
                                    ] as $key => [$label, $defaultUnit])
                                        <tr>
                                            <td class="datasheet-label">{{ $label }}</td>
                                            <td>{{ $fieldInput('process_conditions.'.$key.'.units', $metricUnit($key, $defaultUnit), 'text', 'sheet-unit') }}</td>
                                            @foreach (['minimum', 'normal', 'maximum'] as $point)
                                                <td>
                                                    @if ($key === 'outlet_pressure' && $point === 'maximum')
                                                        <div class="inline-fields">
                                                            {{ $fieldInput('process_conditions.'.$key.'.'.$point, $metricValue($key, $point), 'number', 'sheet-number sheet-inline') }}
                                                            <span>(Shut-off:</span>
                                                            {{ $fieldInput('process_conditions.outlet_pressure.shutoff_delta_p', data_get($process, 'outlet_pressure.shutoff_delta_p'), 'number', 'sheet-number sheet-inline') }}
                                                            <span>)</span>
                                                        </div>
                                                    @else
                                                        {{ $fieldInput('process_conditions.'.$key.'.'.$point, $metricValue($key, $point), 'number', 'sheet-number') }}
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td class="datasheet-label">Density / Specific Gravity / MW</td>
                                        <td>
                                            <div class="inline-fields">
                                            {{ $fieldInput('process_conditions.density.units', $metricUnit('density', 'kg/m³'), 'text', 'sheet-unit sheet-inline') }}
                                            / —
                                            {{ $fieldInput('process_conditions.molecular_weight.units', $metricUnit('molecular_weight', 'g/mol'), 'text', 'sheet-unit sheet-inline') }}
                                            </div>
                                        </td>
                                        @foreach (['minimum', 'normal', 'maximum'] as $point)
                                            <td><div class="inline-fields">
                                                {{ $fieldInput('process_conditions.density.'.$point, $metricValue('density', $point), 'number', 'sheet-number sheet-inline') }}
                                                /
                                                {{ $fieldInput('process_conditions.specific_gravity.'.$point, $metricValue('specific_gravity', $point), 'number', 'sheet-number sheet-inline') }}
                                                /
                                                {{ $fieldInput('process_conditions.molecular_weight.'.$point, $metricValue('molecular_weight', $point), 'number', 'sheet-number sheet-inline') }}
                                            </div></td>
                                        @endforeach
                                    </tr>
                                    <tr>
                                        <td class="datasheet-label">Viscosity / Vapor Pressure (Pv)</td>
                                        <td><div class="inline-fields">
                                            {{ $fieldInput('process_conditions.viscosity.units', $metricUnit('viscosity', 'cP'), 'text', 'sheet-unit sheet-inline') }}
                                            /
                                            {{ $fieldInput('process_conditions.vapor_pressure.units', $metricUnit('vapor_pressure', 'bar(a)'), 'text', 'sheet-unit sheet-inline') }}
                                        </div></td>
                                        @foreach (['minimum', 'normal', 'maximum'] as $point)
                                            <td><div class="inline-fields">
                                                {{ $fieldInput('process_conditions.viscosity.'.$point, $metricValue('viscosity', $point), 'number', 'sheet-number sheet-inline') }}
                                                /
                                                {{ $fieldInput('process_conditions.vapor_pressure.'.$point, $metricValue('vapor_pressure', $point), 'number', 'sheet-number sheet-inline') }}
                                            </div></td>
                                        @endforeach
                                    </tr>
                                    <tr><td class="datasheet-label">Design Pressure / Temp.</td><td class="text-center">—</td><td colspan="3"><div class="inline-fields">{{ $fieldInput('process_conditions.design_pressure', $process['design_pressure'] ?? '', 'text', 'sheet-inline sheet-flex') }} @ {{ $fieldInput('process_conditions.design_temperature', $process['design_temperature'] ?? '', 'text', 'sheet-inline sheet-flex') }}</div></td></tr>
                                </table>

                                <table class="datasheet-table">
                                    <colgroup><col style="width:25%"><col style="width:25%"><col style="width:25%"><col style="width:25%"></colgroup>
                                    <tr class="datasheet-title"><th colspan="4">C. VALVE BODY &amp; MECHANICAL CONSTRUCTION</th></tr>
                                    <tr>
                                        <td class="datasheet-label">Nominal Valve Size (DN / NPS)</td>
                                        <td>{{ $fieldInput('mechanical.nominal_size', $nominalSize) }}</td>
                                        <td class="datasheet-label">End Connections</td>
                                        <td><div class="inline-fields">{{ $snapshotInput('dimension.end_connection', $dimension['end_connection'] ?? '', 'text', 'sheet-inline sheet-medium') }} ({{ $snapshotInput('model.end_connection_standard', $model['end_connection_standard'] ?? '', 'text', 'sheet-inline sheet-medium') }})</div></td>
                                    </tr>
                                    <tr><td class="datasheet-label">Pressure Class / Rating</td><td>{{ $snapshotInput('dimension.pressure_class', $dimension['pressure_class'] ?? '') }}</td><td class="datasheet-label">Flange Facing &amp; Finish</td><td>{{ $snapshotInput('model.flange_facing_finish', $model['flange_facing_finish'] ?? '') }}</td></tr>
                                    <tr><td class="datasheet-label">Design Standard (API / ASME)</td><td>{{ $snapshotInput('model.design_standard', $model['design_standard'] ?? '') }}</td><td class="datasheet-label">Bonnet / Cover Type</td><td>{{ $snapshotInput('model.bonnet_cover_type', $model['bonnet_cover_type'] ?? '') }}</td></tr>
                                    <tr><td class="datasheet-label">Face-to-Face Standard</td><td>{{ $snapshotInput('model.face_to_face_standard', $model['face_to_face_standard'] ?? '') }}</td><td class="datasheet-label">Bore / Port Configuration</td><td>{{ $snapshotInput('model.bore_port_configuration', $model['bore_port_configuration'] ?? '') }}</td></tr>
                                </table>

                                <table class="datasheet-table">
                                    <colgroup><col style="width:25%"><col style="width:25%"><col style="width:25%"><col style="width:25%"></colgroup>
                                    <tr class="datasheet-title"><th colspan="4">D. MATERIALS OF CONSTRUCTION (BILL OF MATERIALS)</th></tr>
                                    @forelse ($bomRows as $pair)
                                        <tr>
                                            @foreach ($pair as $index => $row)
                                                <td class="datasheet-label">
                                                    @php
                                                        $groupPath = 'bom.'.$index.'.component_group';
                                                        $partPath = 'bom.'.$index.'.part_name';
                                                        $groupValue = old('snapshot_edits.'.$groupPath, $row['component_group'] ?? '');
                                                        $partValue = old('snapshot_edits.'.$partPath, $row['part_name'] ?? '');
                                                        $sameBomLabel = filled($groupValue)
                                                            && $normalizeBomLabel($groupValue) === $normalizeBomLabel($partValue);
                                                    @endphp
                                                    @if ($sameBomLabel)
                                                        {{ $snapshotInput($groupPath, $groupValue) }}
                                                        {{ $snapshotInput($partPath, $partValue, 'hidden') }}
                                                    @else
                                                        {{ $snapshotInput($groupPath, $groupValue) }}<br>
                                                        <span class="text-[9px]">{{ $snapshotInput($partPath, $partValue) }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="inline-fields">
                                                        {{ $snapshotInput('bom.'.$index.'.material_code', $row['material_code'] ?? '', 'text', 'sheet-inline sheet-medium') }} —
                                                        {{ $snapshotInput('bom.'.$index.'.material_name', $row['material_name'] ?? '', 'text', 'sheet-inline sheet-flex') }}
                                                    </div>
                                                    <span class="text-[9px]">
                                                            <span class="inline-fields">
                                                                Qty: {{ $snapshotInput('bom.'.$index.'.quantity_per_valve', $row['quantity_per_valve'] ?? 1, 'number', 'sheet-number sheet-inline') }}
                                                                {{ $snapshotInput('bom.'.$index.'.unit', $row['unit'] ?? 'ea', 'text', 'sheet-unit sheet-inline') }}
                                                                — {{ $snapshotInput('bom.'.$index.'.notes', $row['notes'] ?? '', 'text', 'sheet-inline sheet-flex') }}
                                                            </span>
                                                    </span>
                                                </td>
                                            @endforeach
                                            @if (count($pair) === 1)
                                                <td></td><td></td>
                                            @endif
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center">No BOM materials configured</td></tr>
                                    @endforelse
                                </table>
                            </div>
                        </div>
                    </section>

                    <section class="datasheet-page-card rounded-xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-700 dark:bg-white sm:p-5">
                        <div class="datasheet-scroll">
                            <div class="datasheet-page text-[10px] leading-tight text-slate-800">
                                <table id="control-performance-section" class="datasheet-table {{ $category === 'Control / Modulating' ? '' : 'hidden' }}">
                                    <colgroup><col style="width:25%"><col style="width:25%"><col style="width:25%"><col style="width:25%"></colgroup>
                                    <tr class="datasheet-title"><th colspan="4">E. CONTROL VALVE SIZING &amp; PERFORMANCE (COMPLETE FOR MODULATING SERVICE)</th></tr>
                                    <tr>
                                        <td class="datasheet-label">Flow Characteristic</td>
                                        <td>{{ $snapshotInput('model.flow_characteristic', $model['flow_characteristic'] ?? '') }}</td>
                                        <td class="datasheet-label">Valve Travel (% Open)</td>
                                        <td><div class="inline-fields">
                                            @foreach (['minimum' => 'Min', 'normal' => 'Norm', 'maximum' => 'Max'] as $point => $label)
                                                {{ $label }}: {{ $fieldInput('control_performance.valve_travel.'.$point, data_get($performance, 'valve_travel.'.$point), 'number', 'sheet-number sheet-inline') }}%
                                            @endforeach
                                        </div></td>
                                    </tr>
                                    <tr>
                                        <td class="datasheet-label">Required Cv / Kv (Min/Norm/Max)</td>
                                        <td>
                                            <div class="inline-fields">
                                            {{ $fieldInput('control_performance.required_capacity.minimum', data_get($performance, 'required_capacity.minimum'), 'number', 'sheet-number') }} /
                                            {{ $fieldInput('control_performance.required_capacity.normal', data_get($performance, 'required_capacity.normal'), 'number', 'sheet-number') }} /
                                            {{ $fieldInput('control_performance.required_capacity.maximum', data_get($performance, 'required_capacity.maximum'), 'number', 'sheet-number') }}
                                            ({{ $fieldInput('control_performance.required_capacity.units', data_get($performance, 'required_capacity.units', 'Cv'), 'text', 'sheet-unit sheet-inline') }})
                                            </div>
                                        </td>
                                        <td class="datasheet-label">Predicted Noise (dBA)</td>
                                        <td>{{ $fieldInput('control_performance.predicted_noise', $performance['predicted_noise'] ?? '') }}</td>
                                    </tr>
                                    <tr><td class="datasheet-label">Selected Rated Cv / Kv</td><td><div class="inline-fields">{{ $fieldInput('control_performance.selected_rated_cv', $performance['selected_rated_cv'] ?? '', 'number', 'sheet-number sheet-inline') }} Cv ({{ $fieldInput('control_performance.selected_rated_kv', $performance['selected_rated_kv'] ?? '', 'number', 'sheet-number sheet-inline') }} Kv)</div></td><td class="datasheet-label">Cavitation / Flashing Status</td><td>{{ $fieldInput('control_performance.cavitation_status', $performance['cavitation_status'] ?? '') }}</td></tr>
                                </table>

                                <table class="datasheet-table">
                                    <colgroup><col style="width:25%"><col style="width:25%"><col style="width:25%"><col style="width:25%"></colgroup>
                                    <tr class="datasheet-title"><th colspan="4">F. ACTUATION &amp; ACCESSORIES</th></tr>
                                    <tr><td class="datasheet-label">Operator / Actuator Type</td><td>{{ $fieldInput('actuation.actuator_type', $actuation['actuator_type'] ?? '') }}</td><td class="datasheet-label">Supply Air / Power Rating</td><td>{{ $fieldInput('actuation.supply_rating', $actuation['supply_rating'] ?? '') }}</td></tr>
                                    <tr><td class="datasheet-label">Fail-Safe Action</td><td>{{ $fieldInput('actuation.fail_safe_action', $actuation['fail_safe_action'] ?? '') }}</td><td class="datasheet-label">Stroking Time (Open / Close)</td><td>{{ $fieldInput('actuation.stroking_time', $actuation['stroking_time'] ?? '') }}</td></tr>
                                    <tr>
                                        <td class="datasheet-label">Mounted Accessories</td>
                                        <td colspan="3">
                                            <div class="grid grid-cols-2 gap-x-3 gap-y-1 sm:grid-cols-3">
                                                @foreach ($accessories as $accessory)
                                                    <label class="inline-flex items-center gap-1">
                                                        <input type="checkbox" name="datasheet_fields[actuation][mounted_accessories][]" value="{{ $accessory->value }}" @checked(in_array($accessory->value, old('datasheet_fields.actuation.mounted_accessories', $actuation['mounted_accessories'] ?? []), true))>
                                                        {{ $accessory->value }}
                                                    </label>
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                </table>

                                <table class="datasheet-table">
                                    <colgroup><col style="width:25%"><col style="width:25%"><col style="width:25%"><col style="width:25%"></colgroup>
                                    <tr class="datasheet-title"><th colspan="4">G. TESTING, INSPECTION &amp; COMPLIANCE</th></tr>
                                    <tr><td class="datasheet-label">Hydrostatic / Seat Test Std.</td><td>{{ $fieldInput('testing_compliance.hydrostatic_seat_test_standard', $testing['hydrostatic_seat_test_standard'] ?? '') }}</td><td class="datasheet-label">Seat Leakage Class</td><td>{{ $fieldInput('testing_compliance.seat_leakage_class', $testing['seat_leakage_class'] ?? '') }}</td></tr>
                                    <tr><td class="datasheet-label">Material Certification</td><td>{{ $fieldInput('testing_compliance.material_certification', $testing['material_certification'] ?? '') }}</td><td class="datasheet-label">NDT / NDE Requirements</td><td>{{ $fieldInput('testing_compliance.ndt_requirements', $testing['ndt_requirements'] ?? '') }}</td></tr>
                                    <tr>
                                        <td class="datasheet-label">Special Compliance</td>
                                        <td colspan="3">
                                            <div class="grid grid-cols-2 gap-x-3 gap-y-1">
                                                @foreach ($complianceOptions as $requirement)
                                                    <label class="inline-flex items-center gap-1">
                                                        <input type="checkbox" name="datasheet_fields[testing_compliance][special_compliance][]" value="{{ $requirement->value }}" @checked(in_array($requirement->value, old('datasheet_fields.testing_compliance.special_compliance', $testing['special_compliance'] ?? []), true))>
                                                        {{ $requirement->value }}
                                                    </label>
                                                @endforeach
                                            </div>
                                            <label class="mt-2 block">Other compliance</label>
                                            {{ $fieldInput('testing_compliance.other_compliance', $testing['other_compliance'] ?? '') }}
                                        </td>
                                    </tr>
                                    <tr><td class="datasheet-label">Notes / Vendor Remarks</td><td colspan="3"><textarea name="notes" aria-label="Notes / vendor remarks" rows="2" maxlength="5000" class="sheet-input">{{ old('notes', $snapshot['notes'] ?? '') }}</textarea></td></tr>
                                </table>

                                <table class="datasheet-table">
                                    <colgroup><col style="width:10%"><col style="width:18%"><col style="width:24%"><col style="width:16%"><col style="width:16%"><col style="width:16%"></colgroup>
                                    <tr class="datasheet-subhead"><th>Rev</th><th>Date</th><th>Revision Description</th><th>Prepared By</th><th>Checked By</th><th>Approved By</th></tr>
                                    <tr>
                                        <td class="text-center">{{ $revisionNumber }}</td>
                                        <td class="text-center">{{ $revisionDate }}</td>
                                        <td>{{ $fieldInput('revision.description', $revision['description'] ?? 'Issued for Design / Inquiry') }}</td>
                                        <td>{{ $fieldInput('revision.prepared_by', $revision['prepared_by'] ?? '') }}</td>
                                        <td>{{ $fieldInput('revision.checked_by', $revision['checked_by'] ?? '') }}</td>
                                        <td>{{ $fieldInput('revision.approved_by', $revision['approved_by'] ?? '') }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </section>

                    <div class="flex justify-end">
                        <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Create datasheet</button>
                    </div>
                </div>
            </form>

            <form id="back-to-datasheet-setup" method="POST" action="{{ route('tenant.datasheets.editor.back', $draft) }}" class="hidden">
                @csrf
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            const categorySelect = document.getElementById('snapshot-valve-category');
            const controlSection = document.getElementById('control-performance-section');

            function updateControlPerformanceFields() {
                const isModulating = categorySelect.value === 'Control / Modulating';
                controlSection.classList.toggle('hidden', !isModulating);
                controlSection.querySelectorAll('input, select, textarea').forEach((field) => {
                    field.disabled = !isModulating;
                });
            }

            categorySelect.addEventListener('change', updateControlPerformanceFields);
            updateControlPerformanceFields();
        </script>
    @endpush
</x-layouts.dashboard>
