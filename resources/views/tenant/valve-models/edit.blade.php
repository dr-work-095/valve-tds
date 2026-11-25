<x-layouts.dashboard :page-title="'Edit valve model'" :admin-mode="false" :tenant="$tenant" :user="$user">
    <div class="p-4 sm:p-6 lg:p-8">
        <h1 class="mt-3 mb-5 text-2xl font-semibold text-slate-900">Edit valve model</h1>
        @if (session('status'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif
        @if ($readiness_missing !== [])
            <div class="mb-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <p class="font-semibold">This model is not ready for selection.</p>
                <ul class="mt-1 list-disc pl-5">@foreach ($readiness_missing as $requirement)<li>{{ $requirement }}</li>@endforeach</ul>
            </div>
        @endif
        <div class="border-b border-slate-200">
            <nav class="flex gap-6" aria-label="Valve model sections">
                <button type="button" data-tab="details" class="model-tab border-b-2 border-blue-600 px-1 py-3 text-sm font-semibold text-blue-700">Model details</button>
                <button type="button" data-tab="dimensions" id="dimensions" class="model-tab border-b-2 border-transparent px-1 py-3 text-sm font-semibold text-slate-500">Dimensions</button>
                <button type="button" data-tab="bom" id="bom" class="model-tab border-b-2 border-transparent px-1 py-3 text-sm font-semibold text-slate-500">BOM</button>
            </nav>
        </div>
        <div data-tab-panel="details" class="pt-6">
            <form method="POST" action="{{ route('tenant.valve-models.update', $valveModel) }}" enctype="multipart/form-data">
                @method('PUT')
                @include('tenant.valve-models.form', ['valveModel' => $valveModel])
                <button type="submit" class="mt-5 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">Save valve model</button>
            </form>
        </div>
        <div data-tab-panel="dimensions" class="hidden pt-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-900">Dimension matrix</h2>
                <button type="button" data-open-modal="dimension-modal" class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white">Add dimension</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead><tr><th class="px-3 py-2">Size</th><th class="px-3 py-2">DN</th><th class="px-3 py-2">Class</th><th class="px-3 py-2">End connection</th><th class="px-3 py-2">Face-to-face</th><th class="px-3 py-2">Weight</th><th class="px-3 py-2">Actions</th></tr></thead>
                    <tbody>
                        @forelse ($dimensions as $dimension)
                            <tr class="border-t border-slate-100">
                                <td class="px-3 py-3">{{ $dimension->size_inch }}</td>
                                <td class="px-3 py-3">{{ $dimension->size_dn ?: '-' }}</td>
                                <td class="px-3 py-3">{{ $dimension->pressure_class }}</td>
                                <td class="px-3 py-3">{{ $dimension->end_connection }}</td>
                                <td class="px-3 py-3">{{ $dimension->face_to_face_mm !== null ? number_format((float) $dimension->face_to_face_mm, 2).' mm' : '-' }}</td>
                                <td class="px-3 py-3">{{ $dimension->weight_kg !== null ? number_format((float) $dimension->weight_kg, 2).' kg' : '-' }}</td>
                                <td class="px-3 py-3">
                                    <form method="POST" action="{{ route('tenant.valve-models.dimensions.destroy', [$valveModel, $dimension]) }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-sm font-semibold text-red-700">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-3 py-6 text-center text-slate-500">No dimensions configured.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div data-tab-panel="bom" class="hidden pt-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-900">Bill of materials</h2>
                <button type="button" data-open-modal="bom-modal" class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white">Add BOM row</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead><tr><th class="px-3 py-2">Part</th><th class="px-3 py-2">Name</th><th class="px-3 py-2">Standard material</th><th class="px-3 py-2">Actions</th></tr></thead>
                    <tbody>
                        @forelse ($bomRows as $bom)
                            <tr class="border-t border-slate-100">
                                <td class="px-3 py-3">{{ $bom->part_number }}</td>
                                <td class="px-3 py-3">{{ $bom->part_name }}</td>
                                <td class="px-3 py-3">{{ $bom->standardMaterial?->code ?: '-' }}</td>
                                <td class="px-3 py-3">
                                    <form method="POST" action="{{ route('tenant.valve-models.bom.destroy', [$valveModel, $bom]) }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-sm font-semibold text-red-700">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-6 text-center text-slate-500">No BOM rows configured.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @include('tenant.valve-models.modals', ['valveModel' => $valveModel, 'materials' => $materials])
</x-layouts.dashboard>
