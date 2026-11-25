<x-layouts.dashboard :page-title="'Valve models'" :admin-mode="false" :tenant="$tenant" :user="$user">
    <div class="mb-6 flex items-end justify-between gap-3">
        <a href="{{ route('tenant.valve-models.create') }}" class="ml-auto inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-blue-500/20 transition-all hover:bg-blue-700">Add model</a>
    </div>
    @if (session('status')) <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div> @endif
    <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700/60 dark:bg-slate-800">
        <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-700/60">
            <h2 class="text-base font-bold text-slate-900 dark:text-white">Valve models</h2>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Manage valve designs, standards, and configurations.</p>
        </div>
        <div class="overflow-x-auto">
            <table id="valveModelsTable" class="w-full text-left text-sm"><thead><tr><th class="px-6">Name</th><th class="px-6">Category</th><th class="px-6">Design standard</th><th class="px-6">Testing standard</th><th class="px-6">Status</th><th class="px-6">Actions</th></tr></thead><tbody></tbody></table>
        </div>
    </section>
    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
        <script>
            $(function () {
                $('#valveModelsTable').DataTable({
                    processing:true, serverSide:true, pageLength:10, lengthMenu:[10,25,50,100], scrollX:true,
                    dom:'rt<"dt-footer"ip>',
                    pagingType:'simple_numbers',
                    ajax: @json(route('tenant.valve-models.data')),
                    columns:[
                        {data:'name'}, {data:'category'}, {data:'design_standard'}, {data:'testing_standard'},
                        {data:'is_active', orderable:false},
                        {data:'edit_url', orderable:false}
                    ],
                    createdRow: function (row, data) {
                        const status = row.querySelectorAll('td')[4];
                        status.innerHTML = '';
                        const badge = document.createElement('span');
                        badge.className = data.is_active
                            ? 'inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700'
                            : 'inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600';
                        badge.textContent = data.is_active ? 'Active' : 'Inactive';
                        status.appendChild(badge);

                        const action = row.querySelectorAll('td')[5];
                        action.innerHTML = '';
                        const link = document.createElement('a');
                        link.className = 'inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs font-semibold text-blue-700 transition-colors hover:bg-blue-50 hover:text-blue-800';
                        link.href = data.edit_url;
                        link.textContent = 'Edit';
                        action.appendChild(link);
                    }
                });
            });
        </script>
    @endpush
</x-layouts.dashboard>
