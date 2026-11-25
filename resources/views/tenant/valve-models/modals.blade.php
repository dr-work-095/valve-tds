<dialog id="dimension-modal"
    style="position: fixed !important; left: 50% !important; top: 50% !important; transform: translate(-50%, -50%) !important; margin: 0 !important;"
    class="max-h-[calc(100vh-2rem)] w-[calc(100%-2rem)] max-w-3xl overflow-y-auto rounded-xl border border-slate-200 p-0 shadow-xl backdrop:bg-slate-900/40">
    <form method="POST" action="{{ route('tenant.valve-models.dimensions.store', $valveModel) }}">
        @csrf
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">Add dimension</h2>
            <button type="button" data-close-modal="dimension-modal" class="text-slate-500 hover:text-slate-800" aria-label="Close">&times;</button>
        </div>
        <div class="grid gap-4 px-5 py-5 sm:grid-cols-2">
            @foreach ([['size_inch','Size inch','size-inch-values','2"','required'],['size_dn','DN','size-dn-values','DN50',''],['pressure_class','Pressure class','pressure-class-values','Class 150','required'],['end_connection','End connection','end-connection-values','Flanged RF','required']] as $field)
                <div>
                    <label for="dimension_{{ $field[0] }}" class="mb-1 block text-sm font-medium text-slate-700">{{ $field[1] }}</label>
                    <input id="dimension_{{ $field[0] }}" name="{{ $field[0] }}" list="{{ $field[2] }}" value="{{ old($field[0]) }}" maxlength="50" {{ $field[4] ? 'required' : '' }} class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @error($field[0])<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            @endforeach
            <datalist id="size-inch-values"><option value="1/2&quot;"><option value="2&quot;"><option value="4&quot;"><option value="12&quot;"></datalist>
            <datalist id="size-dn-values"><option value="DN15"><option value="DN50"><option value="DN100"><option value="DN300"></datalist>
            <datalist id="pressure-class-values"><option value="Class 150"><option value="Class 300"><option value="Class 600"><option value="PN16"></datalist>
            <datalist id="end-connection-values"><option value="Flanged RF"><option value="Butt Weld"><option value="RTJ"><option value="Threaded"></datalist>
            @foreach ([['face_to_face_mm','Face-to-face mm'],['end_to_end_mm','End-to-end mm'],['flange_od_mm','Flange OD mm'],['center_to_top_mm','Center-to-top mm'],['weight_kg','Weight kg']] as $field)
                <div>
                    <label for="dimension_{{ $field[0] }}" class="mb-1 block text-sm font-medium text-slate-700">{{ $field[1] }}</label>
                    <input id="dimension_{{ $field[0] }}" name="{{ $field[0] }}" type="number" min="0" step="0.01" value="{{ old($field[0]) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @error($field[0])<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            @endforeach
        </div>
        <div class="flex justify-end gap-3 border-t border-slate-200 px-5 py-4">
            <button type="button" data-close-modal="dimension-modal" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Add dimension</button>
        </div>
    </form>
</dialog>

<dialog id="bom-modal"
    style="position: fixed !important; left: 50% !important; top: 50% !important; transform: translate(-50%, -50%) !important; margin: 0 !important;"
    class="max-h-[calc(100vh-2rem)] w-[calc(100%-2rem)] max-w-2xl overflow-y-auto rounded-xl border border-slate-200 p-0 shadow-xl backdrop:bg-slate-900/40">
    <form method="POST" action="{{ route('tenant.valve-models.bom.store', $valveModel) }}">
        @csrf
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">Add BOM row</h2>
            <button type="button" data-close-modal="bom-modal" class="text-slate-500 hover:text-slate-800" aria-label="Close">&times;</button>
        </div>
        <div class="grid gap-4 px-5 py-5 sm:grid-cols-2">
            <div>
                <label for="bom_part_number" class="mb-1 block text-sm font-medium text-slate-700">Part number</label>
                <input id="bom_part_number" name="part_number" type="number" min="1" value="{{ old('part_number') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @error('part_number')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="bom_part_name" class="mb-1 block text-sm font-medium text-slate-700">Part name</label>
                <input id="bom_part_name" name="part_name" list="bom-part-names" value="{{ old('part_name') }}" required maxlength="150" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <datalist id="bom-part-names"><option value="Body"><option value="Bonnet"><option value="Wedge"><option value="Stem"><option value="Seat Ring"><option value="Ball"><option value="Gasket"><option value="Bolting"></datalist>
                @error('part_name')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="bom_standard_material_id" class="mb-1 block text-sm font-medium text-slate-700">Standard material</label>
                <select id="bom_standard_material_id" name="standard_material_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Select material</option>
                    @foreach ($materials as $material)<option value="{{ $material->id }}" @selected((string) old('standard_material_id') === (string) $material->id)>{{ $material->code }} - {{ $material->name }}</option>@endforeach
                </select>
                @error('standard_material_id')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="bom_optional_material_ids" class="mb-1 block text-sm font-medium text-slate-700">Optional material overrides</label>
                <select id="bom_optional_material_ids" name="optional_material_ids[]" multiple size="5" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @foreach ($materials as $material)<option value="{{ $material->id }}" @selected(in_array((string) $material->id, array_map('strval', (array) old('optional_material_ids', []))))>{{ $material->code }} - {{ $material->name }}</option>@endforeach
                </select>
            </div>
        </div>
        <div class="flex justify-end gap-3 border-t border-slate-200 px-5 py-4">
            <button type="button" data-close-modal="bom-modal" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Add BOM row</button>
        </div>
    </form>
</dialog>

@push('scripts')
<script>
    document.querySelectorAll('[data-open-modal]').forEach((button) => {
        button.addEventListener('click', () => document.getElementById(button.dataset.openModal)?.showModal());
    });
    document.querySelectorAll('[data-close-modal]').forEach((button) => {
        button.addEventListener('click', () => document.getElementById(button.dataset.closeModal)?.close());
    });
    const tabs = document.querySelectorAll('.model-tab');
    const panels = document.querySelectorAll('[data-tab-panel]');
    function activateTab(name) {
        tabs.forEach((tab) => {
            const active = tab.dataset.tab === name;
            tab.classList.toggle('border-blue-600', active);
            tab.classList.toggle('text-blue-700', active);
            tab.classList.toggle('border-transparent', !active);
            tab.classList.toggle('text-slate-500', !active);
        });
        panels.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.tabPanel !== name));
    }
    tabs.forEach((tab) => tab.addEventListener('click', () => activateTab(tab.dataset.tab)));
    activateTab(window.location.hash.replace('#', '') || 'details');
    @if ($errors->hasAny(['size_inch','size_dn','pressure_class','end_connection','face_to_face_mm','end_to_end_mm','flange_od_mm','center_to_top_mm','weight_kg']))
        activateTab('dimensions');
        document.getElementById('dimension-modal')?.showModal();
    @elseif ($errors->hasAny(['part_number','part_name','standard_material_id','optional_material_ids']))
        activateTab('bom');
        document.getElementById('bom-modal')?.showModal();
    @endif
</script>
@endpush
