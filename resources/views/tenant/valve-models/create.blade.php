<x-layouts.dashboard :page-title="'Add valve model'" :admin-mode="false" :tenant="$tenant" :user="$user">
    <div class="p-4 sm:p-6 lg:p-8">        
        <h1 class="mt-3 mb-5 text-2xl font-semibold text-slate-900">Add valve model</h1>
        <form method="POST" action="{{ route('tenant.valve-models.store') }}" enctype="multipart/form-data">
            @include('tenant.valve-models.form')
            <button type="submit" class="mt-5 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">Create valve model</button>
        </form>
    </div>
</x-layouts.dashboard>
