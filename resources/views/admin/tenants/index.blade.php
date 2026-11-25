@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
    <script>
        $(function () {
            const table = $('#tenantsTable').DataTable({
                serverSide: true,
                searching: true,
                dom: 't<"dt-footer"ip>',
                pagingType: 'simple_numbers',
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                autoWidth: false,
                order: [[0, 'asc']],
                ajax: {
                    url: @json(route('admin.tenants.data')),
                    data: function (request) {
                        request.plan = $('#tenantPlan').val();
                        request.status = $('#tenantStatus').val();
                    }
                },
                columns: [
                    { data: 'company_name', render: DataTable.render.text() },
                    { data: 'plan', render: DataTable.render.text() },
                    { data: 'status', render: DataTable.render.text() },
                    { data: 'subscription_ends_at', render: DataTable.render.text() },
                    { data: 'provisioning_status', render: DataTable.render.text(), orderable: false },
                    {
                        data: 'edit_url',
                        orderable: false,
                        searchable: false,
                        render: function (url) {
                            return $('<a>', {
                                class: 'inline-flex items-center rounded-lg px-2.5 py-1.5 text-xs font-semibold text-blue-700 transition-colors hover:bg-blue-50 hover:text-blue-800',
                                href: url,
                                text: 'Manage'
                            }).prop('outerHTML');
                        }
                    }
                ],
                language: { emptyTable: 'No tenants have been created yet.' }
            });

            let searchTimeout;
            $('#tenantSearch').on('input', function () {
                const searchValue = this.value;
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function () {
                    table.search(searchValue).draw();
                }, 300);
            });
            $('#tenantPlan, #tenantStatus').on('change', function () {
                table.draw();
            });
        });
    </script>
@endpush

<x-layouts.dashboard :page-title="'Tenants'" :admin-mode="true" :tenant="null" :user="auth('admin')->user()">
    @if (session('status'))
        <div role="status" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div role="alert" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="overflow-hidden dark:bg-slate-800">
        <h1 class="sr-only">Tenant management</h1>
        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-700/60 sm:px-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-medium text-slate-900 dark:text-white">All tenants</h2>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Search and filter tenant records from the central database.</p>
                </div>
                <div class="flex items-center gap-3">
                    <label class="sr-only" for="tenantSearch">Search tenants</label>
                    <input id="tenantSearch" type="search" placeholder="Search tenants"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 outline-none transition-colors placeholder:text-slate-400 focus:border-blue-500 focus:bg-white dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-100 sm:w-64">
                    <a href="{{ route('admin.tenants.create') }}" class="inline-flex shrink-0 items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-blue-500/20 transition-all hover:bg-blue-700">
                        <i data-lucide="plus" class="mr-2 h-4 w-4" aria-hidden="true"></i>
                        Add tenant
                    </a>
                </div>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <label class="sr-only" for="tenantPlan">Filter by plan</label>
                <select id="tenantPlan" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">All plans</option>
                    @foreach ($plans as $plan)
                        <option value="{{ $plan }}">{{ ucfirst($plan) }}</option>
                    @endforeach
                </select>
                <label class="sr-only" for="tenantStatus">Filter by status</label>
                <select id="tenantStatus" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">All statuses</option>
                    @foreach (\App\Models\Tenant::STATUSES as $status)
                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="overflow-x-auto px-3 sm:px-5">
            <table id="tenantsTable" class="w-full text-left text-sm">
                <thead class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-3 py-3">Company</th>
                        <th scope="col" class="px-3 py-3">Plan</th>
                        <th scope="col" class="px-3 py-3">Status</th>
                        <th scope="col" class="px-3 py-3">Subscription ends</th>
                        <th scope="col" class="px-3 py-3">Provisioning</th>
                        <th scope="col" class="px-3 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </section>
</x-layouts.dashboard>
