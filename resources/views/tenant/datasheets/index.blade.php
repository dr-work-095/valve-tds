<x-layouts.dashboard :page-title="'Datasheets'" :admin-mode="false" :tenant="$tenant" :user="$user">
    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif
    <section
        class="overflow-hidden dark:bg-slate-800">
        <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-4 dark:border-slate-700/60">
            <div>
                <h2 class="text-base font-medium text-slate-900 dark:text-white">Datasheets</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Generate and revise valve datasheets.</p>
            </div>
            <div class="flex items-center gap-3">
                <label for="datasheetsSearch" class="sr-only">Search datasheets</label>
                <input id="datasheetsSearch" type="search" placeholder="Search datasheets"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 outline-none transition-colors placeholder:text-slate-400 focus:border-blue-500 focus:bg-white sm:w-64 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-100 dark:placeholder:text-slate-500">
                <a href="{{ route('tenant.datasheets.create') }}"
                    class="inline-flex shrink-0 items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-blue-500/20 transition-all hover:bg-blue-700">
                    <i data-lucide="plus" class="mr-2 h-4 w-4" aria-hidden="true"></i>
                    Create datasheet
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table id="datasheetsTable" class="w-full text-left text-sm">
                <thead><tr><th class="px-6">Document</th><th class="px-6">Revision</th><th class="px-6">Client</th><th class="px-6">Model</th><th class="px-6">Status</th><th class="px-6">Created</th><th class="px-6">Actions</th></tr></thead>
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
            $(function () {
                const table = $('#datasheetsTable').DataTable({
                    serverSide:true,
                    pageLength: 10,
                    lengthMenu: [10, 25, 50, 100],
                    autoWidth: false,
                    dom: 't<"dt-footer"ip>',
                    pagingType: 'simple_numbers',
                    ajax: @json(route('tenant.datasheets.data')),
                    columns: [
                        {data:'document_number'},
                        {data:'revision'},
                        {data:'client_name'},
                        {data:'model_name'},
                        {data:'status', orderable:false},
                        {data:'created_at'},
                        {data:'revision_url', orderable:false, render:function(url, type, row) {
                            if (row.status === 'generated') {
                                return '<form method="POST" action="'+url+'"><input type="hidden" name="_token" value="{{ csrf_token() }}"><button class="inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs font-semibold text-blue-700 transition-colors hover:bg-blue-50 hover:text-blue-800" type="submit">Create revision</button></form>';
                            }
                            if (row.status === 'queued') {
                                return '<span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-sm font-semibold text-amber-700"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>Generating</span>';
                            }
                            return '<span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-sm font-semibold text-slate-600">Retry unavailable</span>';
                        }}
                    ],
                    createdRow: function (row, data) {
                        const status = row.querySelectorAll('td')[4];
                        status.innerHTML = '';
                        const badge = document.createElement('span');
                        const colors = data.status === 'generated'
                            ? 'bg-emerald-50 text-emerald-700'
                            : data.status === 'failed'
                                ? 'bg-red-50 text-red-700'
                                : 'bg-amber-50 text-amber-700';
                        badge.className = 'inline-flex rounded-full px-2.5 py-1 text-sm font-semibold '+colors;
                        badge.textContent = data.status.charAt(0).toUpperCase()+data.status.slice(1);
                        status.appendChild(badge);
                    }
                });
                let searchTimeout;
                $('#datasheetsSearch').on('input', function () {
                    const searchValue = this.value;
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(function () {
                        table.search(searchValue).draw();
                    }, 300);
                });
            });
        </script>
    @endpush
</x-layouts.dashboard>
