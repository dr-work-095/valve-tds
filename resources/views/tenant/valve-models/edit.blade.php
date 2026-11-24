<x-layouts.dashboard :page-title="'Edit valve model'" :admin-mode="false" :tenant="$tenant" :user="$user">
    <a href="{{ route('tenant.valve-models.index') }}" class="text-sm font-medium text-blue-700">← Valve models</a>
    <h1 class="mt-3 mb-5 text-2xl font-semibold text-slate-900">Edit valve model</h1>
    <form method="POST" action="{{ route('tenant.valve-models.update', $valveModel) }}" enctype="multipart/form-data" class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
        @method('PUT')
        @include('tenant.valve-models.form', ['valveModel' => $valveModel])
        <button type="submit" class="mt-5 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">Save valve model</button>
    </form>
</x-layouts.dashboard>
