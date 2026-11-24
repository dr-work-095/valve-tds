@props(['material' => null])
@csrf
<div class="grid gap-4 sm:grid-cols-2">
    <div><label for="code" class="mb-1 block text-sm font-medium text-slate-700">Code</label><input id="code" name="code" value="{{ old('code', $material->code ?? '') }}" required maxlength="100" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">@error('code')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
    <div><label for="name" class="mb-1 block text-sm font-medium text-slate-700">Name</label><input id="name" name="name" value="{{ old('name', $material->name ?? '') }}" maxlength="150" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
    <div><label for="category" class="mb-1 block text-sm font-medium text-slate-700">Category</label><input id="category" name="category" list="material-categories" value="{{ old('category', $material->category ?? '') }}" required maxlength="50" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><datalist id="material-categories"><option value="Body/Bonnet"><option value="Trim/Stem"><option value="Seat/Seal"><option value="Fasteners"></datalist></div>
    <div><label for="is_active" class="mb-1 block text-sm font-medium text-slate-700">Status</label><select id="is_active" name="is_active" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="1" @selected((string)old('is_active', $material->is_active ?? 1)==='1')>Active</option><option value="0" @selected((string)old('is_active', $material->is_active ?? 1)==='0')>Inactive</option></select></div>
</div>
