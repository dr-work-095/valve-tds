<x-layouts.dashboard :page-title="'Materials'" :admin-mode="false" :tenant="$tenant" :user="$user">
    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}</div>
    @endif
    <section
        class="overflow-hidden dark:bg-slate-800">
        <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-4 dark:border-slate-700/60">
            <div>
                <h2 class="text-base font-medium text-slate-900 dark:text-white">Materials</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Manage reusable valve material records.</p>
            </div>
            <div class="flex items-center gap-3">
                <label for="materialsSearch" class="sr-only">Search materials</label>
                <input id="materialsSearch" type="search" placeholder="Search materials"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 outline-none transition-colors placeholder:text-slate-400 focus:border-blue-500 focus:bg-white sm:w-64 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-100 dark:placeholder:text-slate-500">
                <a href="{{ route('tenant.materials.create') }}"
                    class="inline-flex shrink-0 items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-blue-500/20 transition-all hover:bg-blue-700">
                    <i data-lucide="plus" class="mr-2 h-4 w-4" aria-hidden="true"></i>
                    Add material
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table id="materialsTable" class="w-full text-left text-sm">
                <thead>
                    <tr>
                        <th class="px-6">Code</th>
                        <th class="px-6">Name</th>
                        <th class="px-6">Category</th>
                        <th class="px-6">Status</th>
                        <th class="px-6">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </section>
    @push('styles')
        <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
    @endpush
    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
        <script>
            $(function() {
                const table = $('#materialsTable').DataTable({                    
                    serverSide: true,
                    pageLength: 10,
                    lengthMenu: [10, 25, 50, 100],                
                    dom: 't<"dt-footer"ip>',                    
                    ajax: @json(route('tenant.materials.data')),
                    columns: [{
                            data: 'code'
                        }, {
                            data: 'name'
                        }, {
                            data: 'category'
                        },
                        {
                            data: 'is_active',
                            orderable: false
                        },
                        {
                            data: 'edit_url',
                            orderable: false
                        }
                    ],
                    createdRow: function(row, data) {
                        const status = row.querySelectorAll('td')[3];
                        status.innerHTML = '';
                        const badge = document.createElement('span');
                        badge.className = data.is_active ?
                            'inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-sm font-semibold text-emerald-700' :
                            'inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-sm font-semibold text-slate-600';
                        badge.textContent = data.is_active ? 'Active' : 'Inactive';
                        status.appendChild(badge);

                        const action = row.querySelectorAll('td')[4];
                        action.innerHTML = '';
                        const link = document.createElement('a');
                        link.className =
                            'inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs font-semibold text-blue-700 transition-colors hover:bg-blue-50 hover:text-blue-800';
                        link.href = data.edit_url;
                        link.textContent = 'Edit';
                        action.appendChild(link);
                    }
                });

                let searchTimeout;

                $('#materialsSearch').on('input', function() {
                    const searchValue = this.value;
                    clearTimeout(searchTimeout);

                    searchTimeout = setTimeout(function() {
                        table.search(searchValue).draw();
                    }, 300);
                });
            });
        </script>
    @endpush
</x-layouts.dashboard>
