@props(['valveModel' => null])
@csrf
<div class="grid gap-4 sm:grid-cols-2">
    <div><label for="name" class="mb-1 block text-sm font-medium text-slate-700">Model name</label><input id="name" name="name" value="{{ old('name', $valveModel->name ?? '') }}" required maxlength="255" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">@error('name')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
    <div>
        <label for="valve_category" class="mb-1 block text-sm font-medium text-slate-700">Valve Category</label>
        <select id="valve_category" name="valve_category" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">Select a valve category</option>
            @foreach ($valveCategories as $category)
                <option value="{{ $category->value }}" @selected(old('valve_category', $valveModel->valve_category ?? '') === $category->value)>{{ $category->value }}</option>
            @endforeach
        </select>
        @error('valve_category')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="valve_type" class="mb-1 block text-sm font-medium text-slate-700">Valve Type</label>
        <select id="valve_type" name="valve_type" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">Select a valve type</option>
            @foreach ($valveTypes as $type)
                <option value="{{ $type->value }}" @selected(old('valve_type', $valveModel->valve_type ?? '') === $type->value)>{{ $type->value }}</option>
            @endforeach
        </select>
        @error('valve_type')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
    </div>
    <div><label for="design_standard" class="mb-1 block text-sm font-medium text-slate-700">Design standard</label><input id="design_standard" name="design_standard" list="design-standards" value="{{ old('design_standard', $valveModel->design_standard ?? '') }}" required maxlength="255" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><datalist id="design-standards"><option value="API 600"><option value="API 6D"><option value="BS 1414"></datalist></div>
    <div><label for="testing_standard" class="mb-1 block text-sm font-medium text-slate-700">Testing standard</label><input id="testing_standard" name="testing_standard" list="testing-standards" value="{{ old('testing_standard', $valveModel->testing_standard ?? '') }}" required maxlength="255" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><datalist id="testing-standards"><option value="API 598"><option value="EN 12266-1"></datalist></div>
    <div><label for="face_to_face_standard" class="mb-1 block text-sm font-medium text-slate-700">Face-to-face standard</label><input id="face_to_face_standard" name="face_to_face_standard" value="{{ old('face_to_face_standard', $valveModel->face_to_face_standard ?? '') }}" maxlength="255" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
    <div><label for="end_connection_standard" class="mb-1 block text-sm font-medium text-slate-700">End connection standard</label><input id="end_connection_standard" name="end_connection_standard" value="{{ old('end_connection_standard', $valveModel->end_connection_standard ?? '') }}" maxlength="255" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
    <div class="sm:col-span-2 border-t border-slate-200 pt-4"><h2 class="text-sm font-semibold text-slate-900">Reusable construction defaults</h2><p class="mt-1 text-xs text-slate-500">Shared valve construction details; project-specific line size and operating values belong on each datasheet.</p></div>
    <div><label for="flange_facing_finish" class="mb-1 block text-sm font-medium text-slate-700">Flange facing &amp; finish</label><input id="flange_facing_finish" name="flange_facing_finish" value="{{ old('flange_facing_finish', $valveModel->flange_facing_finish ?? '') }}" maxlength="255" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">@error('flange_facing_finish')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
    <div><label for="bonnet_cover_type" class="mb-1 block text-sm font-medium text-slate-700">Bonnet / cover type</label><input id="bonnet_cover_type" name="bonnet_cover_type" value="{{ old('bonnet_cover_type', $valveModel->bonnet_cover_type ?? '') }}" maxlength="255" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">@error('bonnet_cover_type')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
    <div><label for="bore_port_configuration" class="mb-1 block text-sm font-medium text-slate-700">Bore / port configuration</label><input id="bore_port_configuration" name="bore_port_configuration" value="{{ old('bore_port_configuration', $valveModel->bore_port_configuration ?? '') }}" maxlength="255" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">@error('bore_port_configuration')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
    <div><label for="flow_characteristic" class="mb-1 block text-sm font-medium text-slate-700">Flow characteristic</label><input id="flow_characteristic" name="flow_characteristic" value="{{ old('flow_characteristic', $valveModel->flow_characteristic ?? '') }}" maxlength="100" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">@error('flow_characteristic')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
    <div><label for="seat_leakage_class" class="mb-1 block text-sm font-medium text-slate-700">Default seat leakage class</label><input id="seat_leakage_class" name="seat_leakage_class" value="{{ old('seat_leakage_class', $valveModel->seat_leakage_class ?? '') }}" maxlength="100" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">@error('seat_leakage_class')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
    <div><label for="is_active" class="mb-1 block text-sm font-medium text-slate-700">Status</label><select id="is_active" name="is_active" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="1" @selected((string)old('is_active', $valveModel->is_active ?? 1)==='1')>Active</option><option value="0" @selected((string)old('is_active', $valveModel->is_active ?? 1)==='0')>Inactive</option></select></div>
    <div><label for="schematic" class="mb-1 block text-sm font-medium text-slate-700">Schematic</label><input id="schematic" name="schematic" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-slate-600">@error('schematic')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
</div>
