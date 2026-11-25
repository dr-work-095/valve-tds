<x-layouts.dashboard :page-title="'BOM template'" :admin-mode="false" :tenant="$tenant" :user="$user">
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="mb-5 flex items-center justify-between">
            <div><h1 class="text-2xl font-semibold text-slate-900">BOM template</h1><p class="mt-1 text-sm text-slate-500">{{ $valveModel->name }}</p></div>
            <a href="{{ route('tenant.valve-models.edit', $valveModel) }}" class="text-sm font-semibold text-blue-700">Back to model</a>
        </div>
        @if (session('status')) <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div> @endif
        <form method="POST" action="{{ route('tenant.valve-models.bom.store', $valveModel) }}" class="mb-6 grid gap-4 rounded-xl border border-slate-200 p-5 sm:grid-cols-2">
            @csrf
            <div><label for="part_number" class="mb-1 block text-sm font-medium text-slate-700">Part number</label><input id="part_number" name="part_number" type="number" min="1" value="{{ old('part_number') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">@error('part_number')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
            <div><label for="part_name" class="mb-1 block text-sm font-medium text-slate-700">Part name</label><input id="part_name" name="part_name" list="part-names" value="{{ old('part_name') }}" required maxlength="150" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><datalist id="part-names"><option value="Body"><option value="Bonnet"><option value="Wedge"><option value="Stem"><option value="Seat Ring"><option value="Ball"><option value="Gasket"><option value="Bolting"></datalist>@error('part_name')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
            <div><label for="standard_material_id" class="mb-1 block text-sm font-medium text-slate-700">Standard material</label><select id="standard_material_id" name="standard_material_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="">Select material</option>@foreach ($materials as $material)<option value="{{ $material->id }}" @selected((string) old('standard_material_id') === (string) $material->id)>{{ $material->code }} - {{ $material->name }}</option>@endforeach</select>@error('standard_material_id')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
            <div>
                <label for="optional_material_ids" class="mb-1 block text-sm font-medium text-slate-700">Optional material overrides</label>
                <select id="optional_material_ids" name="optional_material_ids[]" multiple size="5" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">@foreach ($materials as $material)<option value="{{ $material->id }}" @selected(in_array((string) $material->id, array_map('strval', (array) old('optional_material_ids', []))))>{{ $material->code }} - {{ $material->name }}</option>@endforeach</select>
            </div>
            <div class="sm:col-span-2"><button type="submit" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">Add BOM row</button></div>
        </form>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead><tr><th class="px-3 py-2">Part</th><th class="px-3 py-2">Name</th><th class="px-3 py-2">Standard material</th><th class="px-3 py-2">Actions</th></tr></thead>
                <tbody>
                    @forelse ($bomRows as $bom)
                        <tr class="border-t border-slate-100"><td class="px-3 py-3">{{ $bom->part_number }}</td><td class="px-3 py-3">{{ $bom->part_name }}</td><td class="px-3 py-3">{{ $bom->standardMaterial?->code ?: '-' }}</td><td class="px-3 py-3"><form method="POST" action="{{ route('tenant.valve-models.bom.destroy', [$valveModel, $bom]) }}">@csrf @method('DELETE')<button type="submit" class="text-sm font-semibold text-red-700">Delete</button></form></td></tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-6 text-center text-slate-500">No BOM rows configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.dashboard>
