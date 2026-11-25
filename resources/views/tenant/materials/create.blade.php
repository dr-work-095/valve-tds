<x-layouts.dashboard :page-title="'Add material'" :admin-mode="false" :tenant="$tenant" :user="$user">
    <div class="p-4 sm:p-6 lg:p-8">    
        <h1 class="mt-3 mb-5 text-2xl font-semibold text-slate-900">Add material</h1>
        <form method="POST" action="{{ route('tenant.materials.store') }}">
            @include('tenant.materials.form')
            <button type="submit" class="mt-5 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">Create material</button>
        </form>
    </div>
</x-layouts.dashboard>
