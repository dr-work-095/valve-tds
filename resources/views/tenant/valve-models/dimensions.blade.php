<x-layouts.dashboard :page-title="'Dimension matrix'" :admin-mode="false" :tenant="$tenant" :user="$user">
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="mb-5 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Dimension matrix</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $valveModel->name }}</p>
            </div>
            <a href="{{ route('tenant.valve-models.edit', $valveModel) }}" class="text-sm font-semibold text-blue-700">Back to model</a>
        </div>
        @if (session('status')) <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div> @endif
        <form method="POST" action="{{ route('tenant.valve-models.dimensions.store', $valveModel) }}" class="mb-6 grid gap-4 rounded-xl border border-slate-200 p-5 sm:grid-cols-2 lg:grid-cols-3">
            @csrf
            @foreach ([['size_inch','Size inch','required'],['size_dn','DN'],['pressure_class','Pressure class','required'],['end_connection','End connection','required']] as $field)
                <div>
                    <label for="{{ $field[0] }}" class="mb-1 block text-sm font-medium text-slate-700">{{ $field[1] }}</label>
                    <input id="{{ $field[0] }}" name="{{ $field[0] }}" value="{{ old($field[0]) }}" {{ ($field[2] ?? '') === 'required' ? 'required' : '' }} maxlength="50" list="{{ str_replace('_','-', $field[0]) }}-values" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @error($field[0])<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            @endforeach
            <datalist id="size-inch-values"><option value="1/2&quot;"><option value="2&quot;"><option value="4&quot;"><option value="12&quot;"></datalist>
            <datalist id="size-dn-values"><option value="DN15"><option value="DN50"><option value="DN100"><option value="DN300"></datalist>
            <datalist id="pressure-class-values"><option value="Class 150"><option value="Class 300"><option value="Class 600"><option value="PN16"></datalist>
            <datalist id="end-connection-values"><option value="Flanged RF"><option value="Butt Weld"><option value="RTJ"><option value="Threaded"></datalist>
            @foreach ([['face_to_face_mm','Face-to-face mm'],['end_to_end_mm','End-to-end mm'],['flange_od_mm','Flange OD mm'],['center_to_top_mm','Center-to-top mm'],['weight_kg','Weight kg']] as $field)
                <div>
                    <label for="{{ $field[0] }}" class="mb-1 block text-sm font-medium text-slate-700">{{ $field[1] }}</label>
                    <input id="{{ $field[0] }}" name="{{ $field[0] }}" type="number" min="0" step="0.01" value="{{ old($field[0]) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @error($field[0])<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            @endforeach
            <div class="sm:col-span-2 lg:col-span-3">
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">Add dimension</button>
            </div>
        </form>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead><tr><th class="px-3 py-2">Size</th><th class="px-3 py-2">DN</th><th class="px-3 py-2">Class</th><th class="px-3 py-2">End</th><th class="px-3 py-2">Face-to-face</th><th class="px-3 py-2">Weight</th><th class="px-3 py-2">Actions</th></tr></thead>
                <tbody>
                    @forelse ($dimensions as $dimension)
                        <tr class="border-t border-slate-100">
                            <td class="px-3 py-3">{{ $dimension->size_inch }}</td><td class="px-3 py-3">{{ $dimension->size_dn ?: '-' }}</td>
                            <td class="px-3 py-3">{{ $dimension->pressure_class }}</td><td class="px-3 py-3">{{ $dimension->end_connection }}</td>
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
</x-layouts.dashboard>
