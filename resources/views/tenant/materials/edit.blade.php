<x-layouts.dashboard :page-title="'Edit material'" :admin-mode="false" :tenant="$tenant" :user="$user">
    <a href="{{ route('tenant.materials.index') }}" class="text-sm font-medium text-blue-700">← Materials</a>
    <h1 class="mt-3 mb-5 text-2xl font-semibold text-slate-900">Edit material</h1>
    <form method="POST" action="{{ route('tenant.materials.update', $material) }}" class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
        @csrf @method('PUT')
        @include('tenant.materials.form', ['material' => $material])
        <button type="submit" class="mt-5 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">Save material</button>
    </form>
</x-layouts.dashboard>
