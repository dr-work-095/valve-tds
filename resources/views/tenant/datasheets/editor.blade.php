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
    $qualityPlanning = $fields['quality_planning'] ?? [];
    $actuation = $fields['actuation'] ?? [];
    $testing = $fields['testing_compliance'] ?? [];
    $shellTestStandard = array_key_exists('shell_test_standard', $testing)
        ? $testing['shell_test_standard']
        : ($testing['hydrostatic_seat_test_standard'] ?? $model['testing_standard'] ?? '');
    $seatTestStandard = array_key_exists('seat_test_standard', $testing)
        ? $testing['seat_test_standard']
        : ($testing['hydrostatic_seat_test_standard'] ?? $model['testing_standard'] ?? '');
    $storedCompliance = $testing['special_compliance'] ?? [];
    $legacyFireSafeSelected = is_array($storedCompliance)
        && in_array(\App\Enums\FireSafeTestStandard::LEGACY_SPECIAL_COMPLIANCE_VALUE, $storedCompliance, true);
    $submittedCompliance = old('datasheet_fields.testing_compliance.special_compliance', $storedCompliance);
    $compliance = is_array($submittedCompliance) ? $submittedCompliance : [];
    $fireSafeTestStandard = old(
        'datasheet_fields.testing_compliance.fire_safe_test_standard',
        array_key_exists('fire_safe_test_standard', $testing)
            ? $testing['fire_safe_test_standard']
            : ($legacyFireSafeSelected ? \App\Enums\FireSafeTestStandard::Api607->value : ''),
    );
    $fireSafeTestStandardOther = old(
        'datasheet_fields.testing_compliance.fire_safe_test_standard_other',
        $testing['fire_safe_test_standard_other'] ?? '',
    );
    $fireSafeOtherSelected = $fireSafeTestStandard === \App\Enums\FireSafeTestStandard::Other->value;
    $revision = $fields['revision'] ?? [];
    $accessories = old('datasheet_fields.actuation.mounted_accessories', $actuation['mounted_accessories'] ?? []);
    $category = old('snapshot_edits.model.valve_category', $model['valve_category'] ?? '');
    $input = function (
        string $section,
        string $path,
        mixed $value = null,
        string $type = 'text',
        string $classes = '',
    ) use ($errors): \Illuminate\Support\HtmlString {
        $name = $section.'['.str_replace('.', '][', $path).']';
        $currentValue = old($section.'.'.$path, $value);
        $ariaLabel = \Illuminate\Support\Str::headline(str_replace('.', ' ', $path));
        $inputType = $type === 'number' ? 'text' : $type;
        $placeholderPath = preg_replace(
            '/^(identification|process_conditions|mechanical|control_performance|quality_planning|actuation|testing_compliance|revision|snapshot_edits)\./',
            '',
            $path,
        );
        $placeholderPath = preg_replace('/^bom\.\d+\./', '', $placeholderPath);
        $placeholderParts = explode('.', $placeholderPath);
        $placeholderLeaf = array_pop($placeholderParts);
        $placeholderLabel = match ($placeholderLeaf) {
            'minimum' => 'Min',
            'normal' => 'Normal',
            'maximum' => 'Max',
            default => \Illuminate\Support\Str::headline(implode(' ', [...$placeholderParts, $placeholderLeaf])),
        };
        if ($type === 'number' && $section === 'datasheet_fields' && str_starts_with($path, 'process_conditions.')) {
            $parameterPath = preg_replace('/\.(minimum|normal|maximum)$/', '', $path);
            $placeholderLabel = match ($parameterPath) {
                'process_conditions.flow_rate' => 'Flow Rate',
                'process_conditions.inlet_pressure' => 'Inlet Pressure',
                'process_conditions.outlet_pressure' => 'Outlet Pressure',
                'process_conditions.operating_temperature' => 'Operating Temperature',
                'process_conditions.density' => 'Density',
                'process_conditions.specific_gravity' => 'Specific Gravity',
                'process_conditions.molecular_weight' => 'Molecular Weight',
                'process_conditions.viscosity' => 'Viscosity',
                'process_conditions.vapor_pressure' => 'Vapor Pressure',
                default => $placeholderLabel,
            };
        }
        $placeholder = match ($type) {
            'number' => ' placeholder="'.e($placeholderLabel).'"',
            'date' => '',
            default => ' placeholder="'.e($placeholderLabel).'"',
        };
        $isInvalid = $errors->has($section.'.'.$path);
        $invalidAttributes = $isInvalid ? ' aria-invalid="true"' : '';

        return new \Illuminate\Support\HtmlString(
            '<input type="'.e($inputType).'" name="'.e($name).'" value="'.e($currentValue).'" aria-label="'.e($ariaLabel).'" class="sheet-input '.e($classes).'"'.$invalidAttributes.$placeholder.'>',
        );
    };
    $topInput = function (string $name, mixed $value = null, string $classes = '') use ($errors): \Illuminate\Support\HtmlString {
        $invalidAttributes = $errors->has($name) ? ' aria-invalid="true"' : '';
        $placeholder = ' placeholder="'.e(\Illuminate\Support\Str::headline($name)).'"';

        return new \Illuminate\Support\HtmlString(
            '<input type="text" name="'.e($name).'" value="'.e(old($name, $value)).'" aria-label="'.e(\Illuminate\Support\Str::headline($name)).'" class="sheet-input '.e($classes).'"'.$invalidAttributes.$placeholder.'>',
        );
    };
    $snapshotInput = fn (string $path, mixed $value = null, string $type = 'text', string $classes = ''): \Illuminate\Support\HtmlString => $input('snapshot_edits', $path, $value, $type, $classes);
    $fieldInput = fn (string $path, mixed $value = null, string $type = 'text', string $classes = ''): \Illuminate\Support\HtmlString => $input('datasheet_fields', $path, $value, $type, $classes);
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

<x-layouts.dashboard :page-title="'Edit datasheet'" :admin-mode="false" :tenant="$tenant" :user="$user" :focus-editor="true">
    @push('styles')
        <style>
            .datasheet-scroll { overflow-x: auto; }
            .datasheet-page { min-width: 760px; }
            .datasheet-table { width: 100%; border-collapse: collapse; table-layout: fixed; margin: 0 0 8px; }
            .datasheet-table td, .datasheet-table th { border: 1px solid #344052; padding: 3px 4px; vertical-align: middle; overflow-wrap: anywhere; }
            .datasheet-title th { padding: 5px 6px; background: #202b3d; color: #fff; text-align: left; font-size: 11px; }
            .datasheet-label { background: #f7f8fa; color: #303b4c; font-weight: 600; }
            .datasheet-subhead td { background: #f0f2f5; color: #202938; text-align: center; font-weight: 600; }
            .sheet-input { display: inline-block; width: 100%; min-width: 0; border: 1px solid #94a3b8; border-radius: 3px; background: #fff; padding: 4px 6px; color: #202938; font: inherit; font-size: 14px; }
            .sheet-input[aria-invalid="true"] { border-color: #dc2626; }
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
            .editor-mode-button { border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 12px; color: #475569; font-size: 14px; font-weight: 600; }
            .editor-mode-button[aria-pressed="true"] { border-color: #2563eb; background: #eff6ff; color: #1d4ed8; }
            .editor-mode-button:focus-visible { outline: 2px solid #2563eb; outline-offset: 2px; }
            #datasheet-editor[data-mode="edit"] .datasheet-scroll { overflow-x: visible; }
            #datasheet-editor[data-mode="edit"] .datasheet-page { min-width: 0; max-width: 100%; }
            #datasheet-editor[data-mode="edit"] .inline-fields { flex-wrap: wrap; white-space: normal; }
            #datasheet-editor[data-mode="edit"] .sheet-input.sheet-inline { flex: 1 1 4rem; }
            #datasheet-editor[data-mode="edit"] .sheet-input.sheet-number,
            #datasheet-editor[data-mode="edit"] .sheet-input.sheet-number.sheet-inline { width: 3.5rem; }
            #datasheet-editor[data-mode="edit"] .sheet-input.sheet-number.sheet-inline { flex: 0 1 3.5rem; }
            #datasheet-editor[data-mode="edit"] .sheet-input.sheet-unit { width: 4.5rem; }
            #datasheet-editor[data-mode="edit"] .sheet-input.sheet-short { width: 5rem; }
            #datasheet-editor[data-mode="edit"] .sheet-input.sheet-medium { width: 8rem; }
            #datasheet-editor[data-mode="edit"] .sheet-input.sheet-line-size { width: 6rem; }
            #datasheet-editor[data-mode="preview"] .sheet-input { min-height: 0; border-color: transparent; border-radius: 0; background: transparent; color: inherit; padding: 2px 3px; }
            #datasheet-editor[data-mode="preview"] select.sheet-input { appearance: none; }
            #datasheet-editor[data-mode="preview"] textarea.sheet-input { resize: none; }
            #datasheet-editor[data-mode="preview"] .sheet-input::placeholder { color: transparent; }
            .datasheet-page-container { max-width: 1200px; }
            @media (max-width: 767px) {
                .datasheet-page-container { padding: 10px; }
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

            <form id="datasheet-editor" method="POST" action="{{ route('tenant.datasheets.store') }}" data-mode="edit">
                @csrf
                <input type="hidden" name="draft_id" value="{{ $draft }}">
                <input type="hidden" name="valve_model_id" value="{{ $selection['valve_model_id'] }}">
                <input type="hidden" name="dimension_id" value="{{ $selection['dimension_id'] }}">
                @foreach ($bomSelections as $index => $bomSelection)
                    <input type="hidden" name="bom_materials[{{ $index }}][bom_template_id]" value="{{ $bomSelection['bom_template_id'] }}">
                    <input type="hidden" name="bom_materials[{{ $index }}][material_id]" value="{{ $bomSelection['material_id'] }}">
                @endforeach

                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">Step 2 of 2</p>
                        <h1 class="text-lg font-semibold text-slate-900 dark:text-white">Review and edit datasheet</h1>
                    </div>
                    <div class="flex items-center gap-2" role="group" aria-label="Editor mode">
                        <button id="editor-mode-edit" type="button" class="editor-mode-button" aria-pressed="true">Edit</button>
                        <button id="editor-mode-preview" type="button" class="editor-mode-button" aria-pressed="false">Preview</button>
                    </div>
                    <div class="flex items-center gap-2">
                        <button form="back-to-datasheet-setup" type="submit" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Back</button>
                        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Create datasheet</button>
                    </div>
                </div>
                <p id="editor-mode-status" class="sr-only" aria-live="polite">Edit mode. Datasheet fields are editable.</p>

                <nav id="datasheet-section-nav" aria-label="Datasheet sections" class="mb-4 flex flex-wrap gap-x-4 gap-y-2 border-b border-slate-200 pb-3 text-sm dark:border-slate-700">
                    <a class="font-medium text-slate-600 underline-offset-4 hover:text-blue-700 hover:underline dark:text-slate-300 dark:hover:text-blue-300" href="#section-a">A. Identification</a>
                    <a class="font-medium text-slate-600 underline-offset-4 hover:text-blue-700 hover:underline dark:text-slate-300 dark:hover:text-blue-300" href="#section-b">B. Process conditions</a>
                    <a class="font-medium text-slate-600 underline-offset-4 hover:text-blue-700 hover:underline dark:text-slate-300 dark:hover:text-blue-300" href="#section-c">C. Mechanical</a>
                    <a class="font-medium text-slate-600 underline-offset-4 hover:text-blue-700 hover:underline dark:text-slate-300 dark:hover:text-blue-300" href="#section-d">D. BOM</a>
                    <a id="control-performance-nav" class="font-medium text-slate-600 underline-offset-4 hover:text-blue-700 hover:underline dark:text-slate-300 dark:hover:text-blue-300" href="#section-e" @if ($category !== 'Control / Modulating') hidden @endif>E. Performance</a>
                    <a class="font-medium text-slate-600 underline-offset-4 hover:text-blue-700 hover:underline dark:text-slate-300 dark:hover:text-blue-300" href="#section-f">F. Actuation</a>
                    <a class="font-medium text-slate-600 underline-offset-4 hover:text-blue-700 hover:underline dark:text-slate-300 dark:hover:text-blue-300" href="#section-g">G. Testing</a>
                    <a class="font-medium text-slate-600 underline-offset-4 hover:text-blue-700 hover:underline dark:text-slate-300 dark:hover:text-blue-300" href="#section-h">H. Quality &amp; Planning</a>
                </nav>

                <div class="grid gap-5">
                    <div id="datasheet-sections" class="grid gap-5">
                    <section class="datasheet-page-container p-3 sm:p-5">
                        <div class="datasheet-scroll">
                            <div class="datasheet-page text-[13px] leading-tight text-slate-800 dark:text-slate-100">
                                <table class="datasheet-table">
                                    <tr>
                                        <td class="w-[55%]">
                                            <div class="text-base font-bold tracking-wide">INDUSTRIAL VALVE ENGINEERING<br>DATASHEET</div>
                                            <div class="mt-1 text-[13px] text-slate-600">{{ $company['company_name'] ?? 'Standard Specification Form (Isolation, Automated On/Off & Control Valves)' }}</div>
                                        </td>
                                        <td class="w-[45%]">
                                            <table class="w-full border-collapse text-[13px]">
                                                <tr><td class="w-[36%] border-0 font-semibold">Document No.:</td><td class="border-0">{{ $topInput('document_number', $selection['document_number'] ?? '') }}</td></tr>
                                                <tr><td class="border-0 font-semibold">Client:</td><td class="border-0">{{ $topInput('client_name', $selection['client_name']) }}</td></tr>
                                                <tr><td class="border-0 font-semibold">Revision / Status:</td><td class="border-0"><div class="inline-fields">Rev 0 — {{ $fieldInput('document_status', $fields['document_status'] ?? 'Issued for Inquiry', 'text', 'sheet-inline sheet-flex') }}</div></td></tr>
                                                <tr><td class="border-0 font-semibold">Date / Project Ref.:</td><td class="border-0"><div class="inline-fields">{{ $revisionDate }} / {{ $topInput('project_reference', $selection['project_reference'] ?? '', 'sheet-inline sheet-flex') }}</div></td></tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>

                                <table class="datasheet-table">
                                    <colgroup><col style="width:20%"><col style="width:30%"><col style="width:20%"><col style="width:30%"></colgroup>
                                    <tr id="section-a" class="datasheet-title"><th colspan="4">A. IDENTIFICATION &amp; GENERAL DATA</th></tr>
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
                                        <td class="datasheet-label">Valve Type</td>
                                        <td>
                                            <select name="snapshot_edits[model][valve_type]" aria-label="Valve type" class="sheet-input">
                                                @foreach ($valveTypes as $valveType)
                                                    <option value="{{ $valveType->value }}" @selected(old('snapshot_edits.model.valve_type', $model['valve_type'] ?? '') === $valveType->value)>{{ $valveType->value }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="datasheet-label">Valve Model / Pattern</td>
                                        <td>{{ $snapshotInput('model.name', $model['name'] ?? '') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="datasheet-label">Installation Location</td>
                                        <td colspan="3">{{ $fieldInput('identification.installation_location', $identification['installation_location'] ?? '') }}</td>
                                    </tr>
                                </table>

                                <table class="datasheet-table">
                                    <colgroup><col style="width:20%"><col style="width:20%"><col style="width:20%"><col style="width:20%"><col style="width:20%"></colgroup>
                                    <tr id="section-b" class="datasheet-title"><th colspan="5">B. PROCESS &amp; OPERATING CONDITIONS</th></tr>
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
                                    <tr id="section-c" class="datasheet-title"><th colspan="4">C. VALVE BODY &amp; MECHANICAL CONSTRUCTION</th></tr>
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
                                    <colgroup><col style="width:8%"><col style="width:22%"><col style="width:70%"></colgroup>
                                    <tr id="section-d" class="datasheet-title"><th colspan="3">D. MATERIALS OF CONSTRUCTION (BILL OF MATERIALS)</th></tr>
                                    <tr class="datasheet-subhead"><td>S. No.</td><td>Part name</td><td>Material details</td></tr>
                                    @forelse ($bom as $index => $row)
                                        <tr>
                                            <td class="text-center">{{ $loop->iteration }}</td>
                                            <td class="datasheet-label">{{ $snapshotInput('bom.'.$index.'.part_name', $row['part_name'] ?? '') }}</td>
                                            <td>
                                                <div class="inline-fields">
                                                    {{ $snapshotInput('bom.'.$index.'.material_code', $row['material_code'] ?? '', 'text', 'sheet-inline sheet-medium') }} —
                                                    {{ $snapshotInput('bom.'.$index.'.material_name', $row['material_name'] ?? '', 'text', 'sheet-inline sheet-flex') }}
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center">No BOM materials configured</td></tr>
                                    @endforelse
                                </table>
                            </div>
                        </div>
                    </section>

                    <section class="datasheet-page-container p-3 sm:p-5">
                        <div class="datasheet-scroll">
                            <div class="datasheet-page text-[13px] leading-tight text-slate-800 dark:text-slate-100">
                                <table id="control-performance-section" class="datasheet-table {{ $category === 'Control / Modulating' ? '' : 'hidden' }}">
                                    <colgroup><col style="width:25%"><col style="width:25%"><col style="width:25%"><col style="width:25%"></colgroup>
                                    <tr id="section-e" class="datasheet-title"><th colspan="4">E. CONTROL VALVE SIZING &amp; PERFORMANCE (COMPLETE FOR MODULATING SERVICE)</th></tr>
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
                                    <tr id="section-f" class="datasheet-title"><th colspan="4">F. ACTUATION &amp; ACCESSORIES</th></tr>
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
                                    <tr id="section-g" class="datasheet-title"><th colspan="4">G. TESTING, INSPECTION &amp; COMPLIANCE</th></tr>
                                    <tr><td class="datasheet-label">Shell / Body Test Standard</td><td>{{ $fieldInput('testing_compliance.shell_test_standard', $shellTestStandard) }}</td><td class="datasheet-label">Seat / Closure Test Standard</td><td>{{ $fieldInput('testing_compliance.seat_test_standard', $seatTestStandard) }}</td></tr>
                                    <tr><td class="datasheet-label">Seat Leakage Class</td><td>{{ $fieldInput('testing_compliance.seat_leakage_class', $testing['seat_leakage_class'] ?? '') }}</td><td class="datasheet-label">Material Certification</td><td>{{ $fieldInput('testing_compliance.material_certification', $testing['material_certification'] ?? '') }}</td></tr>
                                    <tr><td class="datasheet-label">NDT / NDE Requirements</td><td colspan="3">{{ $fieldInput('testing_compliance.ndt_requirements', $testing['ndt_requirements'] ?? '') }}</td></tr>
                                    <tr>
                                        <td class="datasheet-label">Fire-safe Test Standard</td>
                                        <td colspan="3">
                                            <select id="fire-safe-test-standard" name="datasheet_fields[testing_compliance][fire_safe_test_standard]" aria-label="Fire-safe test standard" class="sheet-input">
                                                <option value="">NIL / Not Required</option>
                                                @foreach ($fireSafeTestStandards as $standard)
                                                    <option value="{{ $standard->value }}" @selected($fireSafeTestStandard === $standard->value)>{{ $standard->value }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                    <tr id="fire-safe-test-standard-other-row" @if (! $fireSafeOtherSelected) hidden @endif>
                                        <td class="datasheet-label">Specify Fire-safe Standard</td>
                                        <td colspan="3">
                                            <input id="fire-safe-test-standard-other" type="text" name="datasheet_fields[testing_compliance][fire_safe_test_standard_other]" value="{{ $fireSafeTestStandardOther }}" aria-label="Specify fire-safe test standard" placeholder="Custom Fire-safe Standard" class="sheet-input" @if ($fireSafeOtherSelected) required @else disabled @endif>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="datasheet-label">Special Compliance</td>
                                        <td colspan="3">
                                            <div class="grid grid-cols-2 gap-x-3 gap-y-1">
                                                @foreach ($complianceOptions as $requirement)
                                                    <label class="inline-flex items-center gap-1">
                                                        <input type="checkbox" name="datasheet_fields[testing_compliance][special_compliance][]" value="{{ $requirement->value }}" @checked(in_array($requirement->value, $compliance, true))>
                                                        {{ $requirement->value }}
                                                    </label>
                                                @endforeach
                                            </div>
                                            <label class="mt-2 block">Other compliance</label>
                                            {{ $fieldInput('testing_compliance.other_compliance', $testing['other_compliance'] ?? '') }}
                                        </td>
                                    </tr>
                                    <tr><td class="datasheet-label">Notes / Vendor Remarks</td><td colspan="3"><textarea name="notes" aria-label="Notes / vendor remarks" placeholder="Notes / Vendor Remarks" rows="2" maxlength="5000" class="sheet-input">{{ old('notes', $snapshot['notes'] ?? '') }}</textarea></td></tr>
                                </table>

                                <table class="datasheet-table">
                                    <colgroup><col style="width:34%"><col style="width:66%"></colgroup>
                                    <tr id="section-h" class="datasheet-title"><th colspan="2">H. QUALITY &amp; PROJECT PLANNING</th></tr>
                                    <tr>
                                        <td class="datasheet-label">Inspection &amp; Test Plan (ITP)</td>
                                        <td>
                                            <select name="datasheet_fields[quality_planning][itp_required]" aria-label="Inspection and test plan requirement" class="sheet-input">
                                                <option value="">Not specified</option>
                                                <option value="Required" @selected(old('datasheet_fields.quality_planning.itp_required', $qualityPlanning['itp_required'] ?? '') === 'Required')>Required</option>
                                                <option value="Not Required" @selected(old('datasheet_fields.quality_planning.itp_required', $qualityPlanning['itp_required'] ?? '') === 'Not Required')>Not Required</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="datasheet-label">Third-party Inspection (TPI)</td>
                                        <td>
                                            <select name="datasheet_fields[quality_planning][third_party_inspection_required]" aria-label="Third-party inspection requirement" class="sheet-input">
                                                <option value="">Not specified</option>
                                                <option value="Required" @selected(old('datasheet_fields.quality_planning.third_party_inspection_required', $qualityPlanning['third_party_inspection_required'] ?? '') === 'Required')>Required</option>
                                                <option value="Not Required" @selected(old('datasheet_fields.quality_planning.third_party_inspection_required', $qualityPlanning['third_party_inspection_required'] ?? '') === 'Not Required')>Not Required</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr><td class="datasheet-label">Witness / Hold Points</td><td><textarea name="datasheet_fields[quality_planning][witness_hold_points]" aria-label="Witness and hold points" placeholder="Witness / Hold Points" rows="2" maxlength="1000" class="sheet-input">{{ old('datasheet_fields.quality_planning.witness_hold_points', $qualityPlanning['witness_hold_points'] ?? '') }}</textarea></td></tr>
                                    <tr><td class="datasheet-label">Required Quality Documents</td><td><textarea name="datasheet_fields[quality_planning][quality_documents_required]" aria-label="Required quality documents" placeholder="Required Quality Documents" rows="2" maxlength="1000" class="sheet-input">{{ old('datasheet_fields.quality_planning.quality_documents_required', $qualityPlanning['quality_documents_required'] ?? '') }}</textarea></td></tr>
                                    <tr><td class="datasheet-label">Required On-site Date</td><td>{{ $fieldInput('quality_planning.required_on_site_date', $qualityPlanning['required_on_site_date'] ?? '', 'date') }}</td></tr>
                                    <tr><td class="datasheet-label">Vendor Document Submission Due Date</td><td>{{ $fieldInput('quality_planning.vendor_document_submission_due_date', $qualityPlanning['vendor_document_submission_due_date'] ?? '', 'date') }}</td></tr>
                                    <tr><td class="datasheet-label">Inspection / FAT Target Date</td><td>{{ $fieldInput('quality_planning.inspection_fat_target_date', $qualityPlanning['inspection_fat_target_date'] ?? '', 'date') }}</td></tr>
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
                    </div>

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
            const controlPerformanceNav = document.getElementById('control-performance-nav');

            function updateControlPerformanceFields() {
                const isModulating = categorySelect.value === 'Control / Modulating';
                controlSection.classList.toggle('hidden', !isModulating);
                controlPerformanceNav.hidden = !isModulating;
                controlSection.querySelectorAll('input, select, textarea').forEach((field) => {
                    field.disabled = !isModulating;
                });
            }

            categorySelect.addEventListener('change', updateControlPerformanceFields);
            updateControlPerformanceFields();

            const fireSafeTestStandardSelect = document.getElementById('fire-safe-test-standard');
            const fireSafeOtherStandardRow = document.getElementById('fire-safe-test-standard-other-row');
            const fireSafeOtherStandardInput = document.getElementById('fire-safe-test-standard-other');

            function updateFireSafeStandardFields() {
                const isOther = fireSafeTestStandardSelect.value === 'Other';
                fireSafeOtherStandardRow.hidden = !isOther;
                fireSafeOtherStandardInput.disabled = !isOther;
                fireSafeOtherStandardInput.required = isOther;
            }

            fireSafeTestStandardSelect.addEventListener('change', updateFireSafeStandardFields);
            updateFireSafeStandardFields();

            const editorForm = document.getElementById('datasheet-editor');
            const datasheetSections = document.getElementById('datasheet-sections');
            const editModeButton = document.getElementById('editor-mode-edit');
            const previewModeButton = document.getElementById('editor-mode-preview');
            const editorModeStatus = document.getElementById('editor-mode-status');

            function setEditorMode(mode) {
                const isEditMode = mode === 'edit';

                editorForm.dataset.mode = mode;
                datasheetSections.inert = !isEditMode;
                editModeButton.setAttribute('aria-pressed', String(isEditMode));
                previewModeButton.setAttribute('aria-pressed', String(!isEditMode));
                editorModeStatus.textContent = isEditMode
                    ? 'Edit mode. Datasheet fields are editable.'
                    : 'Preview mode. Datasheet fields are read-only.';
            }

            editModeButton.addEventListener('click', () => setEditorMode('edit'));
            previewModeButton.addEventListener('click', () => setEditorMode('preview'));
        </script>
    @endpush
</x-layouts.dashboard>
