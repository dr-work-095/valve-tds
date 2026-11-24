@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
    <script>
        $(function () {
            const table = $('#tenantsTable').DataTable({
                processing: true,
                serverSide: true,
                searching: true,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                scrollX: true,
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
                                class: 'font-medium text-blue-700 hover:text-blue-900',
                                href: url,
                                text: 'Manage'
                            }).prop('outerHTML');
                        }
                    }
                ],
                language: { emptyTable: 'No tenants have been created yet.' }
            });

            $('#tenantSearch').on('input', function () {
                table.search(this.value).draw();
            });
            $('#tenantPlan, #tenantStatus').on('change', function () {
                table.draw();
            });
        });
    </script>
@endpush

<x-layouts.dashboard :page-title="'Tenants'" :admin-mode="true" :tenant="null" :user="auth('admin')->user()">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-medium text-blue-700">Administration</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Tenant management</h1>
            <p class="mt-1 text-sm text-slate-500">Manage tenant subscriptions and database provisioning.</p>
        </div>
        <a href="{{ route('admin.tenants.create') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
            Add tenant
        </a>
    </div>

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

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:px-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">All tenants</h2>
                    <p class="mt-1 text-sm text-slate-500">Search and filter tenant records from the central database.</p>
                </div>
                <label class="sr-only" for="tenantSearch">Search tenants</label>
                <input id="tenantSearch" type="search" placeholder="Search companies or IDs"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm sm:max-w-xs">
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
