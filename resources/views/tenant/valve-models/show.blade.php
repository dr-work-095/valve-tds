@php
    $valueOrDash = fn (mixed $value): mixed => filled($value) ? $value : '—';
    $formatNumber = fn (mixed $value, string $unit = ''): string => $value === null || $value === ''
        ? '—'
        : number_format((float) $value, 2).' '.$unit;
    $formatExtraValue = fn (mixed $value): string => is_scalar($value)
        ? (string) $value
        : (json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '—');
    $modelDetails = [
        'Valve category' => $valveModel->valve_category,
        'Valve type' => $valveModel->valve_type,
        'Design standard' => $valveModel->design_standard,
        'Testing standard' => $valveModel->testing_standard,
        'Face-to-face standard' => $valveModel->face_to_face_standard,
        'End connection standard' => $valveModel->end_connection_standard,
        'Flange facing & finish' => $valveModel->flange_facing_finish,
        'Bonnet / cover type' => $valveModel->bonnet_cover_type,
        'Bore / port configuration' => $valveModel->bore_port_configuration,
        'Flow characteristic' => $valveModel->flow_characteristic,
        'Seat leakage class' => $valveModel->seat_leakage_class,
        'Status' => $valveModel->is_active ? 'Active' : 'Inactive',
    ];
@endphp

<x-layouts.dashboard :page-title="'View valve model'" :admin-mode="false" :tenant="$tenant" :user="$user">
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <a href="{{ route('tenant.valve-models.index') }}" class="text-sm font-medium text-blue-700 hover:text-blue-800 dark:text-blue-300 dark:hover:text-blue-200">
                        &larr; Back to valve models
                    </a>
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ $valveModel->name }}</h1>
                        <span @class([
                            'inline-flex rounded-full px-3 py-1 text-xs font-semibold',
                            'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' => $valveModel->is_active,
                            'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300' => ! $valveModel->is_active,
                        ])>
                            {{ $valveModel->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Valve model details, dimensions, and bill of materials.</p>
                </div>
                <a href="{{ route('tenant.valve-models.edit', $valveModel) }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                    Edit valve model
                </a>
            </header>

            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Valve details</h2>
                </div>
                <dl class="grid gap-x-8 gap-y-5 p-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($modelDetails as $label => $value)
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $label }}</dt>
                            <dd class="mt-1 break-words text-sm font-medium text-slate-900 dark:text-slate-100">{{ $valueOrDash($value) }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if (is_array($valveModel->custom_attributes) && $valveModel->custom_attributes !== [])
                    <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-700">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Additional attributes</h3>
                        <dl class="mt-4 grid gap-x-8 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($valveModel->custom_attributes as $key => $value)
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ \Illuminate\Support\Str::headline((string) $key) }}</dt>
                                    <dd class="mt-1 break-words text-sm text-slate-900 dark:text-slate-100">{{ $formatExtraValue($value) }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif
                @if ($schematicUrl)
                    <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-700">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Schematic</h3>
                        <img src="{{ $schematicUrl }}" alt="Schematic for {{ $valveModel->name }}" class="mt-3 max-h-96 max-w-full rounded-lg border border-slate-200 object-contain dark:border-slate-700">
                    </div>
                @endif
            </section>

            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Dimensions</h2>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $valveModel->dimensions->count() }} configured</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1050px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-900/50 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Size</th>
                                <th class="px-4 py-3 font-semibold">DN</th>
                                <th class="px-4 py-3 font-semibold">Pressure class</th>
                                <th class="px-4 py-3 font-semibold">End connection</th>
                                <th class="px-4 py-3 font-semibold">Face-to-face</th>
                                <th class="px-4 py-3 font-semibold">End-to-end</th>
                                <th class="px-4 py-3 font-semibold">Flange OD</th>
                                <th class="px-4 py-3 font-semibold">Center-to-top</th>
                                <th class="px-4 py-3 font-semibold">Weight</th>
                                <th class="px-4 py-3 font-semibold">Additional dimensions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            @forelse ($valveModel->dimensions as $dimension)
                                <tr class="text-slate-700 dark:text-slate-200">
                                    <td class="whitespace-nowrap px-4 py-3">{{ $valueOrDash($dimension->size_inch) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ $valueOrDash($dimension->size_dn) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ $valueOrDash($dimension->pressure_class) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ $valueOrDash($dimension->end_connection) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ $formatNumber($dimension->face_to_face_mm, 'mm') }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ $formatNumber($dimension->end_to_end_mm, 'mm') }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ $formatNumber($dimension->flange_od_mm, 'mm') }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ $formatNumber($dimension->center_to_top_mm, 'mm') }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ $formatNumber($dimension->weight_kg, 'kg') }}</td>
                                    <td class="px-4 py-3">
                                        @forelse ($dimension->extra_dimensions ?? [] as $key => $value)
                                            <div class="whitespace-nowrap">{{ \Illuminate\Support\Str::headline((string) $key) }}: {{ $formatExtraValue($value) }}</div>
                                        @empty
                                            —
                                        @endforelse
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">No dimensions configured.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Bill of materials</h2>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $valveModel->bomTemplates->count() }} configured parts</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1050px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-900/50 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Part no.</th>
                                <th class="px-4 py-3 font-semibold">Component</th>
                                <th class="px-4 py-3 font-semibold">Group</th>
                                <th class="px-4 py-3 font-semibold">Quantity</th>
                                <th class="px-4 py-3 font-semibold">Standard material</th>
                                <th class="px-4 py-3 font-semibold">Optional materials</th>
                                <th class="px-4 py-3 font-semibold">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            @forelse ($valveModel->bomTemplates as $bom)
                                <tr class="align-top text-slate-700 dark:text-slate-200">
                                    <td class="whitespace-nowrap px-4 py-3">{{ $bom->part_number }}</td>
                                    <td class="px-4 py-3 font-medium">{{ $bom->part_name }}</td>
                                    <td class="px-4 py-3">{{ $valueOrDash($bom->component_group) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ rtrim(rtrim(number_format((float) $bom->quantity_per_valve, 3), '0'), '.') }} {{ $bom->unit }}</td>
                                    <td class="px-4 py-3">
                                        @if ($bom->standardMaterial)
                                            <div class="font-medium">{{ $bom->standardMaterial->code }} — {{ $bom->standardMaterial->name }}</div>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @forelse ((array) $bom->optional_material_ids as $materialId)
                                            @php($material = $optionalMaterials->get((int) $materialId))
                                            @if ($material)
                                                <div class="mb-1 last:mb-0">
                                                    <span class="font-medium">{{ $material->code }} — {{ $material->name }}</span>
                                                </div>
                                            @else
                                                <div class="mb-1 last:mb-0">Unavailable material ({{ $materialId }})</div>
                                            @endif
                                        @empty
                                            —
                                        @endforelse
                                    </td>
                                    <td class="max-w-sm whitespace-pre-wrap px-4 py-3">{{ $valueOrDash($bom->notes) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">No BOM materials configured.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-layouts.dashboard>
