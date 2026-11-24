<x-layouts.dashboard :page-title="'Valve models'" :admin-mode="false" :tenant="$tenant" :user="$user">
    <div class="mb-6 flex items-end justify-between gap-3">        
        <a href="{{ route('tenant.valve-models.create') }}" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">Add model</a>
    </div>
    @if (session('status')) <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div> @endif
    <section class="overflow-x-auto rounded-xl border border-slate-200 bg-white p-4">
        <table id="valveModelsTable" class="w-full text-left text-sm"><thead><tr><th>Name</th><th>Category</th><th>Design standard</th><th>Testing standard</th><th>Status</th><th>Actions</th></tr></thead><tbody></tbody></table>
    </section>
    @push('styles') <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css"> @endpush
    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
        <script>
            $(function () {
                $('#valveModelsTable').DataTable({
                    processing:true, serverSide:true, pageLength:10, lengthMenu:[10,25,50,100], scrollX:true,
                    ajax: @json(route('tenant.valve-models.data')),
                    columns:[
                        {data:'name'}, {data:'category'}, {data:'design_standard'}, {data:'testing_standard'},
                        {data:'is_active', render:v => v ? 'Active' : 'Inactive', orderable:false},
                        {data:'edit_url', orderable:false, render:url => `<a class="font-medium text-blue-700" href="${url}">Edit</a>`}
                    ]
                });
            });
        </script>
    @endpush
</x-layouts.dashboard>
