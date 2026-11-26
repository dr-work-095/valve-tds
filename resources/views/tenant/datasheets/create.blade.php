<x-layouts.dashboard :page-title="'Create datasheet'" :admin-mode="false" :tenant="$tenant" :user="$user">
    <div class="p-4 sm:p-6 lg:p-8">
        <h1 class="mt-3 mb-5 text-2xl font-semibold text-slate-900">Create datasheet</h1>
        @if ($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        <form method="POST" action="{{ route('tenant.datasheets.store') }}">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
            <div><label for="client_name" class="mb-1 block text-sm font-medium text-slate-700">Client name</label><input id="client_name" name="client_name" required maxlength="255" value="{{ old('client_name') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">@error('client_name')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
            <div><label for="project_reference" class="mb-1 block text-sm font-medium text-slate-700">Project reference</label><input id="project_reference" name="project_reference" maxlength="255" value="{{ old('project_reference') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">@error('project_reference')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
            <div><label for="document_number" class="mb-1 block text-sm font-medium text-slate-700">Document number</label><input id="document_number" name="document_number" maxlength="100" value="{{ old('document_number') }}" placeholder="Auto-generated" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">@error('document_number')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
            <div>
                <label for="valve_model_id" class="mb-1 block text-sm font-medium text-slate-700">Valve model</label>
                <select id="valve_model_id" name="valve_model_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Select a ready model</option>
                    @foreach ($models as $model)<option value="{{ $model->id }}" @selected((string) old('valve_model_id') === (string) $model->id)>{{ $model->name }} - {{ $model->valve_category }} - {{ $model->valve_type }}</option>@endforeach
                </select>
            </div>
            <div><label for="dimension_id" class="mb-1 block text-sm font-medium text-slate-700">Dimension combination</label><select id="dimension_id" name="dimension_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="">Select a valve model first</option></select>@error('dimension_id')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
            <div class="sm:col-span-2">
                <h2 class="mb-2 text-sm font-semibold text-slate-900">BOM materials</h2>
                <div id="bom-materials" class="grid gap-3"></div>
            </div>
            <div class="sm:col-span-2"><label for="notes" class="mb-1 block text-sm font-medium text-slate-700">Project notes</label><textarea id="notes" name="notes" rows="4" maxlength="5000" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('notes', implode("\n", (array) ($company?->default_notes ? json_decode($company->default_notes, true) : []))) }}</textarea></div>
            </div>
            <button type="submit" class="mt-5 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">Create datasheet</button>
        </form>
    </div>
    @push('scripts')
        <script>
            const modelSelect = document.getElementById('valve_model_id');
            const dimensionSelect = document.getElementById('dimension_id');
            const bomContainer = document.getElementById('bom-materials');

            async function loadModel() {
                dimensionSelect.innerHTML = '<option value="">Loading dimensions...</option>';
                bomContainer.innerHTML = '';
                if (!modelSelect.value) {
                    dimensionSelect.innerHTML = '<option value="">Select a valve model first</option>';
                    return;
                }
                const response = await fetch(@json(route('tenant.datasheets.lookups.model', '__MODEL__')).replace('__MODEL__', modelSelect.value));
                if (!response.ok) {
                    dimensionSelect.innerHTML = '<option value="">Unable to load dimensions</option>';
                    bomContainer.innerHTML = '<p class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">Unable to load BOM configuration.</p>';
                    return;
                }
                const data = await response.json();
                dimensionSelect.innerHTML = '<option value="">Select dimension</option>' + data.dimensions.map((dimension) =>
                    '<option value="'+dimension.id+'">'+dimension.size_inch+' / '+dimension.pressure_class+' / '+dimension.end_connection+'</option>'
                ).join('');
                bomContainer.innerHTML = data.bom.map((bom) => {
                    const allowed = [bom.standard_material_id, ...(bom.optional_material_ids || [])];
                    return '<div class="grid gap-2 rounded-lg border border-slate-200 p-3 sm:grid-cols-[8rem_1fr]">' +
                        '<input type="hidden" name="bom_materials['+bom.id+'][bom_template_id]" value="'+bom.id+'">' +
                        '<div><span class="text-xs text-slate-500">Part '+bom.part_number+'</span><p class="font-medium">'+bom.part_name+'</p></div>' +
                        '<select name="bom_materials['+bom.id+'][material_id]" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="">Select material</option></select>' +
                        '</div>';
                }).join('');
                // Populate from the model lookup's standard materials and allowed IDs.
                const selects = bomContainer.querySelectorAll('select');
                const materials = @json($activeMaterials);
                data.bom.forEach((bom, index) => {
                    const allowed = [bom.standard_material_id, ...(bom.optional_material_ids || [])].map(Number);
                    selects[index].innerHTML = '<option value="">Select material</option>' + materials
                        .filter((material) => allowed.includes(Number(material.id)))
                        .map((material) => '<option value="'+material.id+'">'+material.code+' - '+material.name+'</option>')
                        .join('');
                });
            }
            modelSelect.addEventListener('change', loadModel);
        </script>
    @endpush
</x-layouts.dashboard>
