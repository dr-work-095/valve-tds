@php
    $datasheetFields = old('datasheet_fields', []);
    $processMetrics = [
        'flow_rate' => ['Flow Rate', 'm³/h'],
        'inlet_pressure' => ['Inlet Pressure (P1)', 'bar(g)'],
        'outlet_pressure' => ['Outlet Pressure (P2)', 'bar(g)'],
        'operating_temperature' => ['Operating Temperature (T1)', '°C'],
        'density' => ['Density', 'kg/m³'],
        'specific_gravity' => ['Specific Gravity', ''],
        'molecular_weight' => ['Molecular Weight', ''],
        'viscosity' => ['Viscosity', 'cP'],
        'vapor_pressure' => ['Vapor Pressure (Pv)', 'bar(a)'],
    ];
@endphp

<section class="sm:col-span-2 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
    <h2 class="mb-1 text-base font-semibold text-slate-900 dark:text-white">A. Identification &amp; general data</h2>
    <p class="mb-4 text-xs text-slate-500 dark:text-slate-400">Project-specific identification for this datasheet.</p>
    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ([
            'equipment_tag' => 'Equipment tag number',
            'pid_drawing_number' => 'P&ID drawing number',
            'service_fluid_name' => 'Service / fluid name',
            'piping_line_spec' => 'Piping line number / spec',
            'quantity_required' => 'Quantity required',
            'installation_location' => 'Installation location',
        ] as $key => $label)
            <div>
                <label for="identification_{{ $key }}" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">{{ $label }}</label>
                <input id="identification_{{ $key }}" name="datasheet_fields[identification][{{ $key }}]" value="{{ data_get($datasheetFields, 'identification.'.$key) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
            </div>
        @endforeach
        <div>
            <label for="document_status" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Revision status</label>
            <input id="document_status" name="datasheet_fields[document_status]" value="{{ data_get($datasheetFields, 'document_status', 'Issued for Inquiry') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
        </div>
    </div>
</section>

<section class="sm:col-span-2 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
    <h2 class="mb-1 text-base font-semibold text-slate-900 dark:text-white">B. Process &amp; operating conditions</h2>
    <p class="mb-4 text-xs text-slate-500 dark:text-slate-400">Enter applicable minimum, normal, and maximum operating values.</p>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="fluid_phase_state" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Fluid phase &amp; state</label>
            <input id="fluid_phase_state" name="datasheet_fields[process_conditions][fluid_phase_state]" value="{{ data_get($datasheetFields, 'process_conditions.fluid_phase_state') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
        </div>
        @foreach ($processMetrics as $key => [$label, $defaultUnits])
            <fieldset class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                <legend class="px-1 text-sm font-medium text-slate-700 dark:text-slate-200">{{ $label }}</legend>
                @if ($key !== 'specific_gravity')
                    <label for="process_{{ $key }}_units" class="mb-1 block text-xs text-slate-500">Units</label>
                    <input id="process_{{ $key }}_units" name="datasheet_fields[process_conditions][{{ $key }}][units]" value="{{ data_get($datasheetFields, 'process_conditions.'.$key.'.units', $defaultUnits) }}" class="mb-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                @endif
                <div class="grid grid-cols-3 gap-2">
                    @foreach (['minimum' => 'Min', 'normal' => 'Normal', 'maximum' => 'Max'] as $point => $pointLabel)
                        <div>
                            <label for="process_{{ $key }}_{{ $point }}" class="mb-1 block text-xs text-slate-500">{{ $pointLabel }}</label>
                            <input id="process_{{ $key }}_{{ $point }}" name="datasheet_fields[process_conditions][{{ $key }}][{{ $point }}]" type="number" step="any" value="{{ data_get($datasheetFields, 'process_conditions.'.$key.'.'.$point) }}" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                        </div>
                    @endforeach
                </div>
                @if ($key === 'outlet_pressure')
                    <label for="shutoff_delta_p" class="mt-2 mb-1 block text-xs text-slate-500">Shut-off differential pressure (bar)</label>
                    <input id="shutoff_delta_p" name="datasheet_fields[process_conditions][outlet_pressure][shutoff_delta_p]" type="number" step="any" value="{{ data_get($datasheetFields, 'process_conditions.outlet_pressure.shutoff_delta_p') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                @endif
            </fieldset>
        @endforeach
        @foreach (['design_pressure' => 'Design pressure', 'design_temperature' => 'Design temperature'] as $key => $label)
            <div>
                <label for="{{ $key }}" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">{{ $label }}</label>
                <input id="{{ $key }}" name="datasheet_fields[process_conditions][{{ $key }}]" value="{{ data_get($datasheetFields, 'process_conditions.'.$key) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
            </div>
        @endforeach
    </div>
</section>

<section class="sm:col-span-2 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
    <h2 class="mb-1 text-base font-semibold text-slate-900 dark:text-white">C. Valve body &amp; mechanical construction</h2>
    <p class="mb-4 text-xs text-slate-500 dark:text-slate-400">Construction details come from the selected valve model; enter project-specific line size here.</p>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="mechanical_line_size" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Piping line size</label>
            <input id="mechanical_line_size" name="datasheet_fields[mechanical][line_size]" value="{{ data_get($datasheetFields, 'mechanical.line_size') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
        </div>
    </div>
</section>

<section id="control-performance-fields" class="sm:col-span-2 hidden rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
    <h2 class="mb-1 text-base font-semibold text-slate-900 dark:text-white">E. Control valve sizing &amp; performance</h2>
    <p class="mb-4 text-xs text-slate-500 dark:text-slate-400">Complete for modulating service.</p>
    <div class="grid gap-4 sm:grid-cols-2">
        <fieldset class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
            <legend class="px-1 text-sm font-medium text-slate-700 dark:text-slate-200">Valve travel (% open)</legend>
            <div class="grid grid-cols-3 gap-2">
                @foreach (['minimum' => 'Min', 'normal' => 'Normal', 'maximum' => 'Max'] as $point => $label)
                    <div><label for="travel_{{ $point }}" class="mb-1 block text-xs text-slate-500">{{ $label }}</label><input id="travel_{{ $point }}" name="datasheet_fields[control_performance][valve_travel][{{ $point }}]" type="number" step="any" value="{{ data_get($datasheetFields, 'control_performance.valve_travel.'.$point) }}" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"></div>
                @endforeach
            </div>
        </fieldset>
        <fieldset class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
            <legend class="px-1 text-sm font-medium text-slate-700 dark:text-slate-200">Required Cv / Kv</legend>
            <label for="required_capacity_units" class="mb-1 block text-xs text-slate-500">Units</label>
            <input id="required_capacity_units" name="datasheet_fields[control_performance][required_capacity][units]" value="{{ data_get($datasheetFields, 'control_performance.required_capacity.units', 'Cv') }}" class="mb-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
            <div class="grid grid-cols-3 gap-2">
                @foreach (['minimum' => 'Min', 'normal' => 'Normal', 'maximum' => 'Max'] as $point => $label)
                    <div><label for="capacity_{{ $point }}" class="mb-1 block text-xs text-slate-500">{{ $label }}</label><input id="capacity_{{ $point }}" name="datasheet_fields[control_performance][required_capacity][{{ $point }}]" type="number" step="any" value="{{ data_get($datasheetFields, 'control_performance.required_capacity.'.$point) }}" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"></div>
                @endforeach
            </div>
        </fieldset>
        @foreach ([
            'predicted_noise' => 'Predicted noise',
            'selected_rated_cv' => 'Selected rated Cv',
            'selected_rated_kv' => 'Selected rated Kv',
            'cavitation_status' => 'Cavitation / flashing status',
        ] as $key => $label)
            <div>
                <label for="performance_{{ $key }}" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">{{ $label }}</label>
                <input id="performance_{{ $key }}" name="datasheet_fields[control_performance][{{ $key }}]" value="{{ data_get($datasheetFields, 'control_performance.'.$key) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
            </div>
        @endforeach
    </div>
</section>

<section class="sm:col-span-2 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
    <h2 class="mb-1 text-base font-semibold text-slate-900 dark:text-white">F. Actuation &amp; accessories</h2>
    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ([
            'actuator_type' => 'Operator / actuator type',
            'supply_rating' => 'Supply air / power rating',
            'fail_safe_action' => 'Fail-safe action',
            'stroking_time' => 'Stroking time (open / close)',
        ] as $key => $label)
            <div>
                <label for="actuation_{{ $key }}" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">{{ $label }}</label>
                <input id="actuation_{{ $key }}" name="datasheet_fields[actuation][{{ $key }}]" value="{{ data_get($datasheetFields, 'actuation.'.$key) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
            </div>
        @endforeach
        <fieldset class="sm:col-span-2">
            <legend class="mb-2 text-sm font-medium text-slate-700 dark:text-slate-200">Mounted accessories</legend>
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($accessories as $accessory)
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                        <input type="checkbox" name="datasheet_fields[actuation][mounted_accessories][]" value="{{ $accessory->value }}" @checked(in_array($accessory->value, data_get($datasheetFields, 'actuation.mounted_accessories', []), true))>
                        {{ $accessory->value }}
                    </label>
                @endforeach
            </div>
        </fieldset>
    </div>
</section>

<section class="sm:col-span-2 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
    <h2 class="mb-1 text-base font-semibold text-slate-900 dark:text-white">G. Testing, inspection &amp; compliance</h2>
    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ([
            'hydrostatic_seat_test_standard' => 'Hydrostatic / seat test standard',
            'seat_leakage_class' => 'Seat leakage class',
            'material_certification' => 'Material certification',
            'ndt_requirements' => 'NDT / NDE requirements',
        ] as $key => $label)
            <div>
                <label for="compliance_{{ $key }}" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">{{ $label }}</label>
                <input id="compliance_{{ $key }}" name="datasheet_fields[testing_compliance][{{ $key }}]" value="{{ data_get($datasheetFields, 'testing_compliance.'.$key) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
            </div>
        @endforeach
        <fieldset class="sm:col-span-2">
            <legend class="mb-2 text-sm font-medium text-slate-700 dark:text-slate-200">Special compliance</legend>
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($complianceOptions as $compliance)
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                        <input type="checkbox" name="datasheet_fields[testing_compliance][special_compliance][]" value="{{ $compliance->value }}" @checked(in_array($compliance->value, data_get($datasheetFields, 'testing_compliance.special_compliance', []), true))>
                        {{ $compliance->value }}
                    </label>
                @endforeach
            </div>
            <label for="other_compliance" class="mt-3 mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Other compliance</label>
            <input id="other_compliance" name="datasheet_fields[testing_compliance][other_compliance]" value="{{ data_get($datasheetFields, 'testing_compliance.other_compliance') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
        </fieldset>
        <div class="sm:col-span-2">
            <label for="notes" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Notes / vendor remarks</label>
            <textarea id="notes" name="notes" rows="3" maxlength="5000" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">{{ old('notes', implode("\n", (array) ($company?->default_notes ? json_decode($company->default_notes, true) : []))) }}</textarea>
        </div>
    </div>
</section>

<section class="sm:col-span-2 rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
    <h2 class="mb-4 text-base font-semibold text-slate-900 dark:text-white">Revision sign-off</h2>
    <div class="grid gap-4 sm:grid-cols-2">
        @foreach (['description' => 'Revision description', 'prepared_by' => 'Prepared by', 'checked_by' => 'Checked by', 'approved_by' => 'Approved by'] as $key => $label)
            <div>
                <label for="revision_{{ $key }}" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">{{ $label }}</label>
                <input id="revision_{{ $key }}" name="datasheet_fields[revision][{{ $key }}]" value="{{ data_get($datasheetFields, 'revision.'.$key, $key === 'description' ? 'Issued for Design / Inquiry' : '') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
            </div>
        @endforeach
    </div>
</section>
