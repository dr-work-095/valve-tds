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
        $submittedRows = old('datasheet_fields.'.$repeatableFieldName, $fields[$repeatableFieldName] ?? []);
        $repeatableRows[$repeatableFieldName] = is_array($submittedRows)
            ? array_filter($submittedRows, 'is_array')
            : [];
    }
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
    $documentRevision = old('document_revision', 'Rev 0');
    $documentRevision = is_string($documentRevision) ? $documentRevision : '';
    $submittedRevisionDate = old('datasheet_fields.revision.date', $revision['date'] ?? $revisionDate);
    $revisionDate = is_string($submittedRevisionDate) ? $submittedRevisionDate : '';
    $revisionNumber = preg_match('/\ARev ([0-9]{1,6})\z/', $documentRevision, $revisionMatches) === 1
        ? $revisionMatches[1]
        : '—';
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
    $combineMaterialDetails = fn ($code, $details) => implode(' — ', array_filter(
        [$code, $details],
        fn ($value) => filled($value),
    ));
    $combineMechanicalValue = fn ($value, $units) => implode(' — ', array_filter(
        [$value, $units],
        fn ($part) => filled($part),
    ));
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
    $defaultEndConnections = filled($dimension['end_connection'] ?? null)
        ? $dimension['end_connection'].(filled($model['end_connection_standard'] ?? null) ? ' ('.$model['end_connection_standard'].')' : '')
        : ($model['end_connection_standard'] ?? null);
    $endConnections = array_key_exists('end_connections', $mechanical)
        ? $mechanical['end_connections']
        : $defaultEndConnections;
    $maximumOutletPressure = data_get($process, 'outlet_pressure.maximum');
    $processValueOrDash = fn (string $key, string $point) => filled($metricValue($key, $point))
        ? $metricValue($key, $point)
        : '—';
    $processUnitOrDash = fn (string $key, string $default) => filled($metricUnit($key, $default))
        ? $metricUnit($key, $default)
        : '—';
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
            .repeatable-fields { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)) auto; align-items: start; gap: 8px; }
            .repeatable-control-fields { grid-template-columns: repeat(4, minmax(0, 1fr)) auto; }
            .repeatable-accessory-fields { grid-template-columns: minmax(0, 1fr) minmax(0, 1.2fr) auto; }
            .repeatable-mechanical-fields { grid-template-columns: minmax(0, 1fr) minmax(0, 1.5fr) auto; }
            .repeatable-quality-planning-fields { grid-template-columns: minmax(0, 1fr) minmax(0, 1.5fr) auto; }
            .repeatable-input-with-remove { display: flex; align-items: center; gap: 4px; min-width: 0; }
            .repeatable-input-with-remove .sheet-input { flex: 1 1 auto; }
            .repeatable-add-button { border: 1px dashed #94a3b8; border-radius: 6px; background: #f8fafc; padding: 6px 10px; color: #1d4ed8; font: inherit; font-size: 13px; font-weight: 600; }
            .repeatable-add-button:disabled { cursor: not-allowed; opacity: .55; }
            .repeatable-remove-button { display: inline-flex; width: 30px; height: 30px; flex: 0 0 30px; align-items: center; justify-content: center; padding: 0; border: 1px solid #fca5a5; border-radius: 50%; background: #fee2e2; color: #b91c1c; font: inherit; font-weight: 700; }
            .repeatable-remove-button:hover { border-color: #f87171; background: #fecaca; }
            .repeatable-remove-button:focus-visible { outline: 2px solid #991b1b; outline-offset: 2px; }
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
            #datasheet-editor[data-mode="edit"] .datasheet-page-container { padding: 0; }
            #datasheet-editor[data-mode="edit"] .datasheet-scroll { overflow: visible; }
            #datasheet-editor[data-mode="edit"] .datasheet-page { min-width: 0; max-width: 100%; }
            #datasheet-editor[data-mode="edit"] .datasheet-table.editor-section {
                display: block;
                overflow: hidden;
                border: 1px solid #dbe3ee;
                border-radius: 12px;
                border-spacing: 0;
                background: #fff;
                color: #1e293b;
                margin-bottom: 16px;
            }
            #datasheet-editor[data-mode="edit"] .datasheet-table.editor-section tbody { display: block; }
            #datasheet-editor[data-mode="edit"] .datasheet-table.editor-section colgroup { display: none; }
            #datasheet-editor[data-mode="edit"] .datasheet-table.editor-section tr {
                display: grid;
                grid-template-columns: minmax(120px, .8fr) minmax(0, 1.2fr) minmax(120px, .8fr) minmax(0, 1.2fr);
                align-items: center;
                border-bottom: 1px solid #e2e8f0;
            }
            #datasheet-editor[data-mode="edit"] .datasheet-table.editor-section tr:last-child { border-bottom: 0; }
            #datasheet-editor[data-mode="edit"] .datasheet-table.editor-section tr.datasheet-title { display: block; }
            #datasheet-editor[data-mode="edit"] .datasheet-table.editor-section tr.datasheet-title th {
                display: block;
                border: 0;
                background: #eff6ff;
                color: #1e40af;
                padding: 12px 16px !important;
                font-size: 13px;
                letter-spacing: .025em;
            }
            #datasheet-editor[data-mode="edit"] .datasheet-table.editor-section td {
                min-width: 0;
                border: 0;
                padding: 10px 12px;
                overflow-wrap: anywhere;
            }
            #datasheet-editor[data-mode="edit"] .datasheet-table.editor-section td.datasheet-label {
                background: transparent;
                color: #475569;
                font-size: 13px;
                font-weight: 600;
            }
            #datasheet-editor[data-mode="edit"] .datasheet-table.editor-section td[colspan="3"] { grid-column: span 3; }
            #datasheet-editor[data-mode="edit"] .datasheet-table.editor-section td[colspan="2"] { grid-column: span 2; }
            #datasheet-editor[data-mode="edit"] .datasheet-table.editor-section td[colspan="4"] { grid-column: span 4; }
            #datasheet-editor[data-mode="edit"] .datasheet-table.editor-section td[colspan="5"] { grid-column: span 5; }
            #datasheet-editor[data-mode="preview"] [data-repeatable-add-row],
            #datasheet-editor[data-mode="preview"] [data-repeatable-remove] { display: none; }
            #datasheet-editor[data-mode="edit"] .datasheet-table.process-conditions-section tr {
                grid-template-columns: minmax(150px, 1.3fr) minmax(90px, .7fr) repeat(3, minmax(95px, 1fr));
            }
            #datasheet-editor[data-mode="edit"] .datasheet-table.process-conditions-section tr.process-condition-preview-row,
            #datasheet-editor[data-mode="preview"] .datasheet-table.process-conditions-section tr.process-condition-edit-row { display: none; }
            #datasheet-editor[data-mode="edit"] .datasheet-table.control-performance-section tr.control-performance-preview-row,
            #datasheet-editor[data-mode="preview"] .datasheet-table.control-performance-section tr.control-performance-edit-row { display: none; }
            #datasheet-editor[data-mode="edit"] .control-performance-range-fields {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 8px;
                width: 100%;
            }
            #datasheet-editor[data-mode="edit"] .control-performance-capacity-fields {
                grid-template-columns: minmax(80px, .7fr) repeat(3, minmax(0, 1fr));
            }
            #datasheet-editor[data-mode="edit"] .control-performance-range-field {
                display: grid;
                gap: 4px;
                min-width: 0;
            }
            #datasheet-editor[data-mode="edit"] .control-performance-range-label {
                color: #64748b;
                font-size: 12px;
                font-weight: 600;
            }
            #datasheet-editor[data-mode="edit"] .datasheet-table.process-conditions-section tr.datasheet-subhead {
                background: #f8fafc;
                color: #475569;
                font-size: 12px;
                font-weight: 600;
            }
            #datasheet-editor[data-mode="edit"] .datasheet-table.process-conditions-section tr.datasheet-subhead td {
                text-align: left;
            }
            #datasheet-editor[data-mode="edit"] .datasheet-table.bom-section tr {
                grid-template-columns: minmax(60px, .3fr) minmax(140px, .8fr) minmax(0, 2.9fr);
            }
            #datasheet-editor[data-mode="edit"] .datasheet-table.quality-planning-section tr {
                grid-template-columns: minmax(180px, 1fr) minmax(0, 2fr);
            }
            #datasheet-editor[data-mode="edit"] .datasheet-table.revision-section tr {
                grid-template-columns: repeat(6, minmax(90px, 1fr));
            }
            #datasheet-editor[data-mode="edit"] .datasheet-table.editor-section .sheet-input {
                width: 100%;
                min-width: 0;
            }
            #datasheet-editor[data-mode="edit"] .datasheet-table.process-conditions-section .sheet-input.sheet-number {
                width: 100%;
                min-width: 3.5rem;
            }
            #datasheet-editor[data-mode="edit"] .datasheet-table.editor-section .inline-fields {
                flex-wrap: wrap;
                white-space: normal;
            }
            #datasheet-editor[data-mode="edit"] .datasheet-table.editor-section .sheet-input.sheet-inline.sheet-flex {
                width: auto;
                flex: 1 1 8rem;
            }
            #datasheet-editor[data-mode="edit"] .datasheet-table.editor-section .sheet-input.sheet-medium { width: 100%; }
            #datasheet-editor[data-mode="edit"] .datasheet-table.editor-section .sheet-input.sheet-short { width: 100%; }
            #datasheet-editor[data-mode="edit"] .datasheet-table.editor-section textarea.sheet-input { min-height: 72px; }
            #datasheet-editor[data-mode="edit"] .document-header-table { display: block; border: 0; border-spacing: 0; }
            #datasheet-editor[data-mode="edit"] .document-header-table tbody,
            #datasheet-editor[data-mode="edit"] .document-header-table tr { display: block; }
            #datasheet-editor[data-mode="edit"] .document-header-table .document-brand-cell { display: none; }
            #datasheet-editor[data-mode="edit"] .document-header-table .document-details-cell {
                display: block;
                width: 100%;
                border: 0;
                padding: 0;
            }
            #datasheet-editor[data-mode="edit"] .document-detail-preview-label { display: none; }
            #datasheet-editor[data-mode="edit"] .document-project-reference-fields {
                width: 100%;
            }
            #datasheet-editor[data-mode="edit"] .document-details-section { margin-bottom: 16px; }
            #datasheet-editor[data-mode="preview"] .document-details-section .datasheet-title,
            #datasheet-editor[data-mode="preview"] .document-detail-edit-label { display: none; }
            #datasheet-editor[data-mode="preview"] .document-detail-preview-label { display: inline; }
            #datasheet-editor[data-mode="preview"] .sheet-input { min-height: 0; border-color: transparent; border-radius: 0; background: transparent; color: inherit; padding: 2px 3px; }
            #datasheet-editor[data-mode="preview"] select.sheet-input { appearance: none; }
            #datasheet-editor[data-mode="preview"] textarea.sheet-input { resize: none; }
            #datasheet-editor[data-mode="preview"] .sheet-input::placeholder { color: transparent; }
            .datasheet-page-container { max-width: 1200px; }
            @media (max-width: 767px) {
                .datasheet-page-container { padding: 10px; }
                .datasheet-page { min-width: 760px; }
                #datasheet-editor[data-mode="edit"] .repeatable-fields { grid-template-columns: repeat(2, minmax(0, 1fr)); }
                #datasheet-editor[data-mode="edit"] .repeatable-fields.repeatable-mechanical-fields { grid-template-columns: minmax(0, 1fr) minmax(0, 1.5fr) auto; }
                #datasheet-editor[data-mode="edit"] .repeatable-fields.repeatable-quality-planning-fields { grid-template-columns: minmax(0, 1fr) minmax(0, 1.5fr) auto; }
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
                                <table class="datasheet-table document-header-table">
                                    <tr>
                                        <td class="document-brand-cell w-[55%]">
                                            <div class="text-base font-bold tracking-wide">INDUSTRIAL VALVE ENGINEERING<br>DATASHEET</div>
                                            <div class="mt-1 text-[13px] text-slate-600">{{ $company['company_name'] ?? 'Standard Specification Form (Isolation, Automated On/Off & Control Valves)' }}</div>
                                        </td>
                                        <td class="document-details-cell w-[45%]">
                                            <table class="datasheet-table editor-section document-details-section">
                                                <colgroup><col style="width:20%"><col style="width:30%"><col style="width:20%"><col style="width:30%"></colgroup>
                                                <tr class="datasheet-title"><th colspan="4">DOCUMENT DETAILS</th></tr>
                                                <tr>
                                                    <td class="datasheet-label"><span class="document-detail-edit-label">Document No.</span><span class="document-detail-preview-label">Document No.:</span></td>
                                                    <td>{{ $topInput('document_number', $selection['document_number'] ?? '') }}</td>
                                                    <td class="datasheet-label"><span class="document-detail-edit-label">Client Name</span><span class="document-detail-preview-label">Client:</span></td>
                                                    <td>{{ $topInput('client_name', $selection['client_name']) }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="datasheet-label"><span class="document-detail-edit-label">Revision</span><span class="document-detail-preview-label">Revision:</span></td>
                                                    <td><input id="document-revision-input" type="text" name="document_revision" value="{{ $documentRevision }}" aria-label="Revision" maxlength="10" required placeholder="Rev 0" class="sheet-input" @if ($errors->has('document_revision')) aria-invalid="true" @endif></td>
                                                    <td class="datasheet-label"><span class="document-detail-edit-label">Document Status</span><span class="document-detail-preview-label">Document Status:</span></td>
                                                    <td>{{ $fieldInput('document_status', $fields['document_status'] ?? 'Issued for Inquiry') }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="datasheet-label"><span class="document-detail-edit-label">Date</span><span class="document-detail-preview-label">Date:</span></td>
                                                    <td>{{ $fieldInput('revision.date', $revisionDate, 'date') }}</td>
                                                    <td class="datasheet-label"><span class="document-detail-edit-label">Project Reference</span><span class="document-detail-preview-label">Project Reference:</span></td>
                                                    <td><div class="document-project-reference-fields">{{ $topInput('project_reference', $selection['project_reference'] ?? '', 'document-project-reference-input') }}</div></td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>

                                <table class="datasheet-table editor-section identification-section">
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

                                <table class="datasheet-table editor-section process-conditions-section">
                                    <colgroup><col style="width:20%"><col style="width:20%"><col style="width:20%"><col style="width:20%"><col style="width:20%"></colgroup>
                                    <tbody data-repeatable-container="additional_process_conditions">
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
                                    @foreach ([
                                        'density' => ['Density', 'kg/m³'],
                                        'specific_gravity' => ['Specific Gravity', null],
                                        'molecular_weight' => ['Molecular Weight', 'g/mol'],
                                        'viscosity' => ['Viscosity', 'cP'],
                                        'vapor_pressure' => ['Vapor Pressure (Pv)', 'bar(a)'],
                                    ] as $key => [$label, $defaultUnit])
                                        <tr class="process-condition-edit-row">
                                            <td class="datasheet-label">{{ $label }}</td>
                                            <td>
                                                @if ($defaultUnit === null)
                                                    <span>—</span>
                                                @else
                                                    {{ $fieldInput('process_conditions.'.$key.'.units', $metricUnit($key, $defaultUnit), 'text', 'sheet-unit') }}
                                                @endif
                                            </td>
                                            @foreach (['minimum', 'normal', 'maximum'] as $point)
                                                <td>{{ $fieldInput('process_conditions.'.$key.'.'.$point, $metricValue($key, $point), 'number', 'sheet-number') }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                    <tr class="process-condition-preview-row">
                                        <td class="datasheet-label">Density / Specific Gravity / MW</td>
                                        <td class="text-center">
                                            <span data-condition-preview="density.units">{{ $processUnitOrDash('density', 'kg/m³') }}</span>
                                            / —
                                            <span data-condition-preview="molecular_weight.units">{{ $processUnitOrDash('molecular_weight', 'g/mol') }}</span>
                                        </td>
                                        @foreach (['minimum', 'normal', 'maximum'] as $point)
                                            <td class="text-center">
                                                <span data-condition-preview="density.{{ $point }}">{{ $processValueOrDash('density', $point) }}</span>
                                                /
                                                <span data-condition-preview="specific_gravity.{{ $point }}">{{ $processValueOrDash('specific_gravity', $point) }}</span>
                                                /
                                                <span data-condition-preview="molecular_weight.{{ $point }}">{{ $processValueOrDash('molecular_weight', $point) }}</span>
                                            </td>
                                        @endforeach
                                    </tr>
                                    <tr class="process-condition-preview-row">
                                        <td class="datasheet-label">Viscosity / Vapor Pressure (Pv)</td>
                                        <td class="text-center">
                                            <span data-condition-preview="viscosity.units">{{ $processUnitOrDash('viscosity', 'cP') }}</span>
                                            /
                                            <span data-condition-preview="vapor_pressure.units">{{ $processUnitOrDash('vapor_pressure', 'bar(a)') }}</span>
                                        </td>
                                        @foreach (['minimum', 'normal', 'maximum'] as $point)
                                            <td class="text-center">
                                                <span data-condition-preview="viscosity.{{ $point }}">{{ $processValueOrDash('viscosity', $point) }}</span>
                                                /
                                                <span data-condition-preview="vapor_pressure.{{ $point }}">{{ $processValueOrDash('vapor_pressure', $point) }}</span>
                                            </td>
                                        @endforeach
                                    </tr>
                                    <tr><td class="datasheet-label">Design Pressure / Temp.</td><td class="text-center">—</td><td colspan="3"><div class="inline-fields">{{ $fieldInput('process_conditions.design_pressure', $process['design_pressure'] ?? '', 'text', 'sheet-inline sheet-flex') }} @ {{ $fieldInput('process_conditions.design_temperature', $process['design_temperature'] ?? '', 'text', 'sheet-inline sheet-flex') }}</div></td></tr>
                                    @foreach ($repeatableRows['additional_process_conditions'] as $index => $row)
                                        <tr data-repeatable-row="additional_process_conditions" data-row-index="{{ $index }}">
                                            <td>
                                                <div class="repeatable-input-with-remove">
                                                    {{ $fieldInput('additional_process_conditions.'.$index.'.parameter', $row['parameter'] ?? '') }}
                                                    <button type="button" class="repeatable-remove-button" data-repeatable-remove="additional_process_conditions" aria-label="Remove custom process condition"><i data-lucide="minus" class="h-4 w-4" aria-hidden="true"></i></button>
                                                </div>
                                            </td>
                                            <td>{{ $fieldInput('additional_process_conditions.'.$index.'.units', $row['units'] ?? '') }}</td>
                                            @foreach (['minimum', 'normal', 'maximum'] as $point)
                                                <td>{{ $fieldInput('additional_process_conditions.'.$index.'.'.$point, $row[$point] ?? '', 'number', 'sheet-number') }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                    <tr data-repeatable-add-row>
                                        <td colspan="5"><button type="button" class="repeatable-add-button" data-repeatable-add="additional_process_conditions" data-repeatable-max="20">+ Add process parameter</button></td>
                                    </tr>
                                    </tbody>
                                </table>
                                <template id="additional_process_conditions-template">
                                    <tr data-repeatable-row="additional_process_conditions" data-row-index="__INDEX__">
                                        <td>
                                            <div class="repeatable-input-with-remove">
                                                <input type="text" name="datasheet_fields[additional_process_conditions][__INDEX__][parameter]" aria-label="Custom process parameter" placeholder="Parameter" maxlength="100" class="sheet-input">
                                                <button type="button" class="repeatable-remove-button" data-repeatable-remove="additional_process_conditions" aria-label="Remove custom process condition"><i data-lucide="minus" class="h-4 w-4" aria-hidden="true"></i></button>
                                            </div>
                                        </td>
                                        <td><input type="text" name="datasheet_fields[additional_process_conditions][__INDEX__][units]" aria-label="Custom process condition units" placeholder="Units" maxlength="50" class="sheet-input"></td>
                                        <td><input type="text" name="datasheet_fields[additional_process_conditions][__INDEX__][minimum]" aria-label="Custom process condition minimum" placeholder="Min" maxlength="100" class="sheet-input sheet-number"></td>
                                        <td><input type="text" name="datasheet_fields[additional_process_conditions][__INDEX__][normal]" aria-label="Custom process condition normal" placeholder="Normal" maxlength="100" class="sheet-input sheet-number"></td>
                                        <td><input type="text" name="datasheet_fields[additional_process_conditions][__INDEX__][maximum]" aria-label="Custom process condition maximum" placeholder="Max" maxlength="100" class="sheet-input sheet-number"></td>
                                    </tr>
                                </template>

                                <table class="datasheet-table editor-section mechanical-section">
                                    <colgroup><col style="width:25%"><col style="width:25%"><col style="width:25%"><col style="width:25%"></colgroup>
                                    <tbody data-repeatable-container="additional_mechanical_specs">
                                    <tr id="section-c" class="datasheet-title"><th colspan="4">C. VALVE BODY &amp; MECHANICAL CONSTRUCTION</th></tr>
                                    <tr>
                                        <td class="datasheet-label">Nominal Valve Size (DN / NPS)</td>
                                        <td>{{ $fieldInput('mechanical.nominal_size', $nominalSize) }}</td>
                                        <td class="datasheet-label">End Connections</td>
                                        <td>{{ $fieldInput('mechanical.end_connections', $endConnections) }}</td>
                                    </tr>
                                    <tr><td class="datasheet-label">Pressure Class / Rating</td><td>{{ $snapshotInput('dimension.pressure_class', $dimension['pressure_class'] ?? '') }}</td><td class="datasheet-label">Flange Facing &amp; Finish</td><td>{{ $snapshotInput('model.flange_facing_finish', $model['flange_facing_finish'] ?? '') }}</td></tr>
                                    <tr><td class="datasheet-label">Design Standard (API / ASME)</td><td>{{ $snapshotInput('model.design_standard', $model['design_standard'] ?? '') }}</td><td class="datasheet-label">Bonnet / Cover Type</td><td>{{ $snapshotInput('model.bonnet_cover_type', $model['bonnet_cover_type'] ?? '') }}</td></tr>
                                    <tr><td class="datasheet-label">Face-to-Face Standard</td><td>{{ $snapshotInput('model.face_to_face_standard', $model['face_to_face_standard'] ?? '') }}</td><td class="datasheet-label">Bore / Port Configuration</td><td>{{ $snapshotInput('model.bore_port_configuration', $model['bore_port_configuration'] ?? '') }}</td></tr>
                                    @foreach ($repeatableRows['additional_mechanical_specs'] as $index => $row)
                                        @php
                                            $mechanicalValue = $combineMechanicalValue($row['value'] ?? null, $row['units'] ?? null);
                                        @endphp
                                        <tr data-repeatable-row="additional_mechanical_specs" data-row-index="{{ $index }}">
                                            <td colspan="4">
                                                <div class="repeatable-fields repeatable-mechanical-fields">
                                                    {{ $fieldInput('additional_mechanical_specs.'.$index.'.specification', $row['specification'] ?? '') }}
                                                    {{ $fieldInput('additional_mechanical_specs.'.$index.'.value', $mechanicalValue) }}
                                                    <button type="button" class="repeatable-remove-button" data-repeatable-remove="additional_mechanical_specs" aria-label="Remove mechanical specification"><i data-lucide="minus" class="h-4 w-4" aria-hidden="true"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr data-repeatable-add-row>
                                        <td colspan="4"><button type="button" class="repeatable-add-button" data-repeatable-add="additional_mechanical_specs" data-repeatable-max="20">+ Add mechanical specification</button></td>
                                    </tr>
                                    </tbody>
                                </table>
                                <template id="additional_mechanical_specs-template">
                                    <tr data-repeatable-row="additional_mechanical_specs" data-row-index="__INDEX__">
                                        <td colspan="4">
                                            <div class="repeatable-fields repeatable-mechanical-fields">
                                                <input type="text" name="datasheet_fields[additional_mechanical_specs][__INDEX__][specification]" aria-label="Mechanical specification" placeholder="Specification" maxlength="100" class="sheet-input">
                                                <input type="text" name="datasheet_fields[additional_mechanical_specs][__INDEX__][value]" aria-label="Mechanical specification value" placeholder="Value" maxlength="350" class="sheet-input">
                                                <button type="button" class="repeatable-remove-button" data-repeatable-remove="additional_mechanical_specs" aria-label="Remove mechanical specification"><i data-lucide="minus" class="h-4 w-4" aria-hidden="true"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>

                                <table class="datasheet-table editor-section bom-section">
                                    <colgroup><col style="width:8%"><col style="width:22%"><col style="width:70%"></colgroup>
                                    <tbody data-repeatable-container="additional_bom_items">
                                    <tr id="section-d" class="datasheet-title"><th colspan="3">D. MATERIALS OF CONSTRUCTION (BILL OF MATERIALS)</th></tr>
                                    <tr class="datasheet-subhead"><td>S. No.</td><td>Part name</td><td>Material details</td></tr>
                                    @forelse ($bom as $index => $row)
                                        @php
                                            $materialDetails = array_key_exists('material_details', $row)
                                                ? $row['material_details']
                                                : $combineMaterialDetails($row['material_code'] ?? null, $row['material_name'] ?? null);
                                        @endphp
                                        <tr>
                                            <td class="text-center">{{ $loop->iteration }}</td>
                                            <td class="datasheet-label">{{ $snapshotInput('bom.'.$index.'.part_name', $row['part_name'] ?? '') }}</td>
                                            <td>{{ $snapshotInput('bom.'.$index.'.material_details', $materialDetails) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center">No BOM materials configured</td></tr>
                                    @endforelse
                                    @foreach ($repeatableRows['additional_bom_items'] as $index => $row)
                                        @php
                                            $materialDetails = $combineMaterialDetails($row['material_code'] ?? null, $row['material_details'] ?? null);
                                        @endphp
                                        <tr data-repeatable-row="additional_bom_items" data-row-index="{{ $index }}">
                                            <td class="text-center">{{ count($bom) + $loop->iteration }}</td>
                                            <td>{{ $fieldInput('additional_bom_items.'.$index.'.part_name', $row['part_name'] ?? '') }}</td>
                                            <td>
                                                <div class="inline-fields">
                                                    {{ $fieldInput('additional_bom_items.'.$index.'.material_details', $materialDetails, 'text', 'sheet-inline sheet-flex') }}
                                                    <button type="button" class="repeatable-remove-button" data-repeatable-remove="additional_bom_items" aria-label="Remove BOM item"><i data-lucide="minus" class="h-4 w-4" aria-hidden="true"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr data-repeatable-add-row>
                                        <td colspan="3"><button type="button" class="repeatable-add-button" data-repeatable-add="additional_bom_items" data-repeatable-max="20">+ Add BOM item</button></td>
                                    </tr>
                                    </tbody>
                                </table>
                                <template id="additional_bom_items-template">
                                    <tr data-repeatable-row="additional_bom_items" data-row-index="__INDEX__">
                                        <td class="text-center">+</td>
                                        <td><input type="text" name="datasheet_fields[additional_bom_items][__INDEX__][part_name]" aria-label="Additional BOM part name" placeholder="Part name" maxlength="255" class="sheet-input"></td>
                                        <td>
                                            <div class="inline-fields">
                                                <input type="text" name="datasheet_fields[additional_bom_items][__INDEX__][material_details]" aria-label="Additional BOM material details" placeholder="Material details" maxlength="500" class="sheet-input sheet-inline sheet-flex">
                                                <button type="button" class="repeatable-remove-button" data-repeatable-remove="additional_bom_items" aria-label="Remove BOM item"><i data-lucide="minus" class="h-4 w-4" aria-hidden="true"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </div>
                        </div>
                    </section>

                    <section class="datasheet-page-container p-3 sm:p-5">
                        <div class="datasheet-scroll">
                            <div class="datasheet-page text-[13px] leading-tight text-slate-800 dark:text-slate-100">
                                <table id="control-performance-section" class="datasheet-table editor-section control-performance-section {{ $category === 'Control / Modulating' ? '' : 'hidden' }}">
                                    <colgroup><col style="width:25%"><col style="width:25%"><col style="width:25%"><col style="width:25%"></colgroup>
                                    <tbody data-repeatable-container="additional_control_cases">
                                    <tr id="section-e" class="datasheet-title"><th colspan="4">E. CONTROL VALVE SIZING &amp; PERFORMANCE (COMPLETE FOR MODULATING SERVICE)</th></tr>
                                    <tr class="control-performance-edit-row">
                                        <td class="datasheet-label">Flow Characteristic</td>
                                        <td colspan="3">{{ $snapshotInput('model.flow_characteristic', $model['flow_characteristic'] ?? '') }}</td>
                                    </tr>
                                    <tr class="control-performance-edit-row">
                                        <td class="datasheet-label">Valve Travel (% Open)</td>
                                        <td colspan="3">
                                            <div class="control-performance-range-fields">
                                                @foreach (['minimum' => 'Minimum', 'normal' => 'Normal', 'maximum' => 'Maximum'] as $point => $label)
                                                    <div class="control-performance-range-field">
                                                        <span class="control-performance-range-label">{{ $label }}</span>
                                                        {{ $fieldInput('control_performance.valve_travel.'.$point, data_get($performance, 'valve_travel.'.$point), 'number') }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="control-performance-edit-row">
                                        <td class="datasheet-label">Required Capacity (Cv / Kv)</td>
                                        <td colspan="3">
                                            <div class="control-performance-range-fields control-performance-capacity-fields">
                                                <div class="control-performance-range-field">
                                                    <span class="control-performance-range-label">Units</span>
                                                    {{ $fieldInput('control_performance.required_capacity.units', data_get($performance, 'required_capacity.units', 'Cv')) }}
                                                </div>
                                                @foreach (['minimum' => 'Minimum', 'normal' => 'Normal', 'maximum' => 'Maximum'] as $point => $label)
                                                    <div class="control-performance-range-field">
                                                        <span class="control-performance-range-label">{{ $label }}</span>
                                                        {{ $fieldInput('control_performance.required_capacity.'.$point, data_get($performance, 'required_capacity.'.$point), 'number') }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="control-performance-edit-row">
                                        <td class="datasheet-label">Predicted Noise (dBA)</td>
                                        <td colspan="3">{{ $fieldInput('control_performance.predicted_noise', $performance['predicted_noise'] ?? '') }}</td>
                                    </tr>
                                    <tr class="control-performance-edit-row">
                                        <td class="datasheet-label">Selected Rated Cv</td>
                                        <td colspan="3">{{ $fieldInput('control_performance.selected_rated_cv', $performance['selected_rated_cv'] ?? '', 'number') }}</td>
                                    </tr>
                                    <tr class="control-performance-edit-row">
                                        <td class="datasheet-label">Selected Rated Kv</td>
                                        <td colspan="3">{{ $fieldInput('control_performance.selected_rated_kv', $performance['selected_rated_kv'] ?? '', 'number') }}</td>
                                    </tr>
                                    <tr class="control-performance-edit-row">
                                        <td class="datasheet-label">Cavitation / Flashing Status</td>
                                        <td colspan="3">{{ $fieldInput('control_performance.cavitation_status', $performance['cavitation_status'] ?? '') }}</td>
                                    </tr>
                                    <tr class="control-performance-preview-row">
                                        <td class="datasheet-label">Flow Characteristic</td>
                                        <td><span data-performance-preview="snapshot_edits[model][flow_characteristic]">—</span></td>
                                        <td class="datasheet-label">Valve Travel (% Open)</td>
                                        <td>Min: <span data-performance-preview="datasheet_fields[control_performance][valve_travel][minimum]" data-suffix="%">—</span> &nbsp; Norm: <span data-performance-preview="datasheet_fields[control_performance][valve_travel][normal]" data-suffix="%">—</span> &nbsp; Max: <span data-performance-preview="datasheet_fields[control_performance][valve_travel][maximum]" data-suffix="%">—</span></td>
                                    </tr>
                                    <tr class="control-performance-preview-row">
                                        <td class="datasheet-label">Required Cv / Kv (Min/Norm/Max)</td>
                                        <td>
                                            <span data-performance-preview="datasheet_fields[control_performance][required_capacity][minimum]">—</span> /
                                            <span data-performance-preview="datasheet_fields[control_performance][required_capacity][normal]">—</span> /
                                            <span data-performance-preview="datasheet_fields[control_performance][required_capacity][maximum]">—</span>
                                            (<span data-performance-preview="datasheet_fields[control_performance][required_capacity][units]">—</span>)
                                        </td>
                                        <td class="datasheet-label">Predicted Noise (dBA)</td>
                                        <td><span data-performance-preview="datasheet_fields[control_performance][predicted_noise]">—</span></td>
                                    </tr>
                                    <tr class="control-performance-preview-row">
                                        <td class="datasheet-label">Selected Rated Cv / Kv</td>
                                        <td><span data-performance-preview="datasheet_fields[control_performance][selected_rated_cv]">—</span> Cv (<span data-performance-preview="datasheet_fields[control_performance][selected_rated_kv]">—</span> Kv)</td>
                                        <td class="datasheet-label">Cavitation / Flashing Status</td>
                                        <td><span data-performance-preview="datasheet_fields[control_performance][cavitation_status]">—</span></td>
                                    </tr>
                                    @foreach ($repeatableRows['additional_control_cases'] as $index => $row)
                                        <tr data-repeatable-row="additional_control_cases" data-row-index="{{ $index }}">
                                            <td colspan="4">
                                                <div class="repeatable-fields repeatable-control-fields">
                                                    {{ $fieldInput('additional_control_cases.'.$index.'.case_name', $row['case_name'] ?? '') }}
                                                    {{ $fieldInput('additional_control_cases.'.$index.'.valve_travel', $row['valve_travel'] ?? '') }}
                                                    {{ $fieldInput('additional_control_cases.'.$index.'.required_capacity', $row['required_capacity'] ?? '') }}
                                                    {{ $fieldInput('additional_control_cases.'.$index.'.predicted_noise', $row['predicted_noise'] ?? '') }}
                                                    <button type="button" class="repeatable-remove-button" data-repeatable-remove="additional_control_cases" aria-label="Remove control sizing case"><i data-lucide="minus" class="h-4 w-4" aria-hidden="true"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr data-repeatable-add-row>
                                        <td colspan="4"><button type="button" class="repeatable-add-button" data-repeatable-add="additional_control_cases" data-repeatable-max="20">+ Add sizing case</button></td>
                                    </tr>
                                    </tbody>
                                </table>
                                <template id="additional_control_cases-template">
                                    <tr data-repeatable-row="additional_control_cases" data-row-index="__INDEX__">
                                        <td colspan="4">
                                            <div class="repeatable-fields repeatable-control-fields">
                                                <input type="text" name="datasheet_fields[additional_control_cases][__INDEX__][case_name]" aria-label="Sizing case name" placeholder="Case name" maxlength="100" class="sheet-input">
                                                <input type="text" name="datasheet_fields[additional_control_cases][__INDEX__][valve_travel]" aria-label="Sizing case valve travel" placeholder="Valve travel" maxlength="50" class="sheet-input">
                                                <input type="text" name="datasheet_fields[additional_control_cases][__INDEX__][required_capacity]" aria-label="Sizing case required capacity" placeholder="Required capacity" maxlength="100" class="sheet-input">
                                                <input type="text" name="datasheet_fields[additional_control_cases][__INDEX__][predicted_noise]" aria-label="Sizing case predicted noise" placeholder="Predicted noise" maxlength="100" class="sheet-input">
                                                <button type="button" class="repeatable-remove-button" data-repeatable-remove="additional_control_cases" aria-label="Remove control sizing case"><i data-lucide="minus" class="h-4 w-4" aria-hidden="true"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>

                                <table class="datasheet-table editor-section actuation-section">
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
                                                <div class="mt-3 grid gap-2" data-repeatable-container="additional_accessories">
                                                    @foreach ($repeatableRows['additional_accessories'] as $index => $row)
                                                        <div class="repeatable-fields repeatable-accessory-fields" data-repeatable-row="additional_accessories" data-row-index="{{ $index }}">
                                                            {{ $fieldInput('additional_accessories.'.$index.'.accessory', $row['accessory'] ?? '') }}
                                                            {{ $fieldInput('additional_accessories.'.$index.'.details', $row['details'] ?? '') }}
                                                            <button type="button" class="repeatable-remove-button" data-repeatable-remove="additional_accessories" aria-label="Remove accessory"><i data-lucide="minus" class="h-4 w-4" aria-hidden="true"></i></button>
                                                        </div>
                                                    @endforeach
                                                    <div data-repeatable-add-row>
                                                        <button type="button" class="repeatable-add-button" data-repeatable-add="additional_accessories" data-repeatable-max="20">+ Add accessory</button>
                                                    </div>
                                                </div>
                                        </td>
                                    </tr>
                                </table>
                                <template id="additional_accessories-template">
                                    <div class="repeatable-fields repeatable-accessory-fields" data-repeatable-row="additional_accessories" data-row-index="__INDEX__">
                                        <input type="text" name="datasheet_fields[additional_accessories][__INDEX__][accessory]" aria-label="Additional accessory" placeholder="Accessory" maxlength="100" class="sheet-input">
                                        <input type="text" name="datasheet_fields[additional_accessories][__INDEX__][details]" aria-label="Additional accessory details" placeholder="Details" maxlength="255" class="sheet-input">
                                        <button type="button" class="repeatable-remove-button" data-repeatable-remove="additional_accessories" aria-label="Remove accessory"><i data-lucide="minus" class="h-4 w-4" aria-hidden="true"></i></button>
                                    </div>
                                </template>

                                <table class="datasheet-table editor-section testing-compliance-section">
                                    <colgroup><col style="width:25%"><col style="width:25%"><col style="width:25%"><col style="width:25%"></colgroup>
                                    <tbody data-repeatable-container="additional_compliance">
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
                                    @foreach ($repeatableRows['additional_compliance'] as $index => $row)
                                        <tr data-repeatable-row="additional_compliance" data-row-index="{{ $index }}">
                                            <td colspan="4">
                                                <div class="repeatable-fields">
                                                    {{ $fieldInput('additional_compliance.'.$index.'.item', $row['item'] ?? '') }}
                                                    {{ $fieldInput('additional_compliance.'.$index.'.standard', $row['standard'] ?? '') }}
                                                    {{ $fieldInput('additional_compliance.'.$index.'.status_notes', $row['status_notes'] ?? '') }}
                                                    <button type="button" class="repeatable-remove-button" data-repeatable-remove="additional_compliance" aria-label="Remove testing or compliance item"><i data-lucide="minus" class="h-4 w-4" aria-hidden="true"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr data-repeatable-add-row>
                                        <td colspan="4"><button type="button" class="repeatable-add-button" data-repeatable-add="additional_compliance" data-repeatable-max="20">+ Add testing / compliance item</button></td>
                                    </tr>
                                    <tr><td class="datasheet-label">Notes / Vendor Remarks</td><td colspan="3"><textarea name="notes" aria-label="Notes / vendor remarks" placeholder="Notes / Vendor Remarks" rows="2" maxlength="5000" class="sheet-input">{{ old('notes', $snapshot['notes'] ?? '') }}</textarea></td></tr>
                                    </tbody>
                                </table>
                                <template id="additional_compliance-template">
                                    <tr data-repeatable-row="additional_compliance" data-row-index="__INDEX__">
                                        <td colspan="4">
                                            <div class="repeatable-fields">
                                                <input type="text" name="datasheet_fields[additional_compliance][__INDEX__][item]" aria-label="Testing or compliance item" placeholder="Test / compliance item" maxlength="100" class="sheet-input">
                                                <input type="text" name="datasheet_fields[additional_compliance][__INDEX__][standard]" aria-label="Testing or compliance standard" placeholder="Standard" maxlength="100" class="sheet-input">
                                                <input type="text" name="datasheet_fields[additional_compliance][__INDEX__][status_notes]" aria-label="Testing or compliance status or notes" placeholder="Status / notes" maxlength="255" class="sheet-input">
                                                <button type="button" class="repeatable-remove-button" data-repeatable-remove="additional_compliance" aria-label="Remove testing or compliance item"><i data-lucide="minus" class="h-4 w-4" aria-hidden="true"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>

                                <table class="datasheet-table editor-section quality-planning-section">
                                    <colgroup><col style="width:34%"><col style="width:66%"></colgroup>
                                    <tbody data-repeatable-container="additional_quality_planning">
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
                                    @foreach ($repeatableRows['additional_quality_planning'] as $index => $row)
                                        <tr data-repeatable-row="additional_quality_planning" data-row-index="{{ $index }}">
                                            <td colspan="2">
                                                <div class="repeatable-fields repeatable-quality-planning-fields">
                                                    {{ $fieldInput('additional_quality_planning.'.$index.'.item', $row['item'] ?? '') }}
                                                    {{ $fieldInput('additional_quality_planning.'.$index.'.requirement_notes', $row['requirement_notes'] ?? '') }}
                                                    <button type="button" class="repeatable-remove-button" data-repeatable-remove="additional_quality_planning" aria-label="Remove quality planning item"><i data-lucide="minus" class="h-4 w-4" aria-hidden="true"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr data-repeatable-add-row>
                                        <td colspan="2"><button type="button" class="repeatable-add-button" data-repeatable-add="additional_quality_planning" data-repeatable-max="20">+ Add quality planning item</button></td>
                                    </tr>
                                    </tbody>
                                </table>
                                <template id="additional_quality_planning-template">
                                    <tr data-repeatable-row="additional_quality_planning" data-row-index="__INDEX__">
                                        <td colspan="2">
                                            <div class="repeatable-fields repeatable-quality-planning-fields">
                                                <input type="text" name="datasheet_fields[additional_quality_planning][__INDEX__][item]" aria-label="Quality planning item" placeholder="Planning item" maxlength="100" class="sheet-input">
                                                <input type="text" name="datasheet_fields[additional_quality_planning][__INDEX__][requirement_notes]" aria-label="Quality planning requirement or notes" placeholder="Requirement / notes" maxlength="255" class="sheet-input">
                                                <button type="button" class="repeatable-remove-button" data-repeatable-remove="additional_quality_planning" aria-label="Remove quality planning item"><i data-lucide="minus" class="h-4 w-4" aria-hidden="true"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>

                                <table class="datasheet-table editor-section revision-section">
                                    <colgroup><col style="width:10%"><col style="width:18%"><col style="width:24%"><col style="width:16%"><col style="width:16%"><col style="width:16%"></colgroup>
                                    <tr class="datasheet-subhead"><th>Rev</th><th>Date</th><th>Revision Description</th><th>Prepared By</th><th>Checked By</th><th>Approved By</th></tr>
                                    <tr>
                                        <td id="revision-number-display" class="text-center">{{ $revisionNumber }}</td>
                                        <td class="text-center"><span id="revision-date-display">{{ $revisionDate }}</span></td>
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
            const documentRevisionInput = document.getElementById('document-revision-input');
            const revisionNumberDisplay = document.getElementById('revision-number-display');
            const revisionDateInput = document.querySelector('input[name="datasheet_fields[revision][date]"]');
            const revisionDateDisplay = document.getElementById('revision-date-display');

            function updateRevisionMetadataDisplays() {
                const match = documentRevisionInput.value.match(/^Rev ([0-9]{1,6})$/);
                revisionNumberDisplay.textContent = match ? match[1] : '—';
                revisionDateDisplay.textContent = revisionDateInput.value || '—';
            }

            documentRevisionInput.addEventListener('input', updateRevisionMetadataDisplays);
            revisionDateInput.addEventListener('input', updateRevisionMetadataDisplays);
            updateRevisionMetadataDisplays();

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
            const conditionPreviewFields = editorForm.querySelectorAll('[data-condition-preview]');
            const controlPerformancePreviewFields = editorForm.querySelectorAll('[data-performance-preview]');
            const repeatableGroupUpdates = new Map();

            editorForm.querySelectorAll('[data-repeatable-add]').forEach((addButton) => {
                const group = addButton.dataset.repeatableAdd;
                const container = editorForm.querySelector(`[data-repeatable-container="${group}"]`);
                const template = document.getElementById(`${group}-template`);
                const addRow = container?.querySelector('[data-repeatable-add-row]');
                const templateRow = template?.content.firstElementChild;
                const maxRows = Number(addButton.dataset.repeatableMax);

                if (!container || !templateRow || !addRow || !Number.isInteger(maxRows) || maxRows < 1) {
                    throw new Error(`Repeatable row group "${group}" is not configured correctly.`);
                }

                let nextIndex = 0;
                const getRows = () => container.querySelectorAll(`[data-repeatable-row="${group}"]`);
                getRows().forEach((row) => {
                    const rowIndex = Number(row.dataset.rowIndex);
                    if (Number.isInteger(rowIndex) && rowIndex >= nextIndex) {
                        nextIndex = rowIndex + 1;
                    }
                });

                const updateAddButton = () => {
                    addButton.disabled = getRows().length >= maxRows;
                    addButton.setAttribute('aria-disabled', String(addButton.disabled));
                };
                repeatableGroupUpdates.set(group, updateAddButton);

                addButton.addEventListener('click', () => {
                    if (addButton.disabled) {
                        return;
                    }

                    const row = templateRow.cloneNode(true);
                    row.dataset.rowIndex = String(nextIndex);
                    row.querySelectorAll('[name]').forEach((field) => {
                        field.name = field.name.replace('__INDEX__', String(nextIndex));
                    });
                    nextIndex += 1;
                    container.insertBefore(row, addRow);
                    updateAddButton();
                    row.querySelector('input, select, textarea')?.focus();
                });

                updateAddButton();
            });

            editorForm.addEventListener('click', (event) => {
                if (!(event.target instanceof Element)) {
                    return;
                }

                const removeButton = event.target.closest('[data-repeatable-remove]');
                if (!removeButton) {
                    return;
                }

                const group = removeButton.dataset.repeatableRemove;
                const row = removeButton.closest('[data-repeatable-row]');
                const updateAddButton = repeatableGroupUpdates.get(group);

                if (!row || !updateAddButton) {
                    throw new Error(`Repeatable row group "${group}" is not configured correctly.`);
                }

                row.remove();
                updateAddButton();
            });

            function updateConditionPreview() {
                conditionPreviewFields.forEach((previewField) => {
                    const [parameter, property] = previewField.dataset.conditionPreview.split('.');
                    const fieldName = `datasheet_fields[process_conditions][${parameter}][${property}]`;
                    const input = editorForm.querySelector(`[name="${fieldName}"]`);
                    previewField.textContent = input && input.value.trim() ? input.value : '—';
                });
            }

            editorForm.querySelectorAll('.process-condition-edit-row input').forEach((input) => {
                input.addEventListener('input', updateConditionPreview);
            });
            updateConditionPreview();

            function updateControlPerformancePreview() {
                controlPerformancePreviewFields.forEach((previewField) => {
                    const inputName = previewField.dataset.performancePreview;
                    const input = editorForm.querySelector(`[name="${inputName}"]`);
                    const value = input && input.value.trim() ? input.value : '';
                    previewField.textContent = value
                        ? value + (previewField.dataset.suffix || '')
                        : '—';
                });
            }

            editorForm.querySelectorAll('.control-performance-edit-row input, .control-performance-edit-row select, .control-performance-edit-row textarea').forEach((input) => {
                input.addEventListener('input', updateControlPerformancePreview);
            });
            updateControlPerformancePreview();

            function setEditorMode(mode) {
                const isEditMode = mode === 'edit';

                editorForm.dataset.mode = mode;
                updateConditionPreview();
                updateControlPerformancePreview();
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
