<x-layouts.dashboard :page-title="'Company settings'" :admin-mode="false" :tenant="$tenant" :user="$user">
    <div class="p-4 sm:p-6 lg:p-8">
    @if (session('status'))
        <div role="status" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('tenant.settings.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="company_name" class="mb-1 block text-sm font-medium text-slate-700">Company name</label>
                <input id="company_name" name="company_name" value="{{ old('company_name', $settings->company_name ?? '') }}" required maxlength="255" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @error('company_name') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="address" class="mb-1 block text-sm font-medium text-slate-700">Address</label>
                <textarea id="address" name="address" rows="3" maxlength="5000" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('address', $settings->address ?? '') }}</textarea>
            </div>
            <div>
                <label for="tax_id_gst" class="mb-1 block text-sm font-medium text-slate-700">GSTIN / Tax ID</label>
                <input id="tax_id_gst" name="tax_id_gst" value="{{ old('tax_id_gst', $settings->tax_id_gst ?? '') }}" maxlength="50" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label for="header_text" class="mb-1 block text-sm font-medium text-slate-700">Header text</label>
                <input id="header_text" name="header_text" value="{{ old('header_text', $settings->header_text ?? '') }}" maxlength="255" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label for="footer_text" class="mb-1 block text-sm font-medium text-slate-700">Footer text</label>
                <input id="footer_text" name="footer_text" value="{{ old('footer_text', $settings->footer_text ?? '') }}" maxlength="255" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div class="sm:col-span-2">
                <label for="logo" class="mb-1 block text-sm font-medium text-slate-700">Company logo</label>
                @if (! empty($settings->logo_path))
                    <p class="mb-2 text-xs text-slate-500">A logo is currently stored privately.</p>
                @endif
                <input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-slate-600">
                @error('logo') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
        </div>
        <button type="submit" class="mt-5 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Save settings</button>
    </form>
    </div>
</x-layouts.dashboard>
