<x-layouts.dashboard :page-title="'Dashboard'" :admin-mode="true" :tenant="null" :user="$user">
    <div class="mb-6">
        <p class="text-sm font-medium text-blue-700">Administration</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Tenant overview</h1>
        <p class="mt-1 text-sm text-slate-500">A central view of company workspaces and subscription status.</p>
    </div>

    <section class="grid grid-cols-1 gap-3 sm:grid-cols-3" aria-label="Tenant summary">
        <article class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm font-medium text-slate-500">Total tenants</p>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ number_format($totalTenants) }}</p>
            <p class="mt-1 text-xs text-slate-500">Registered workspaces</p>
        </article>
        <article class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm font-medium text-slate-500">Active tenants</p>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ number_format($activeTenants) }}</p>
            <p class="mt-1 text-xs text-emerald-700">Subscription status active</p>
        </article>
        <article class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm font-medium text-slate-500">Expiring in 7 days</p>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ number_format($expiringTenants) }}</p>
            <p class="mt-1 text-xs text-amber-700">Active or trial subscriptions</p>
        </article>
    </section>

    <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Recently added tenants</h2>
                <p class="mt-1 text-sm text-slate-500">Review recently created workspaces and their subscription status.</p>
            </div>
            <a href="{{ route('admin.tenants.index') }}" class="w-fit rounded-md border border-slate-200 px-3 py-2 text-sm font-medium text-blue-700 hover:bg-blue-50">Manage tenants</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3 sm:px-6">Company</th>
                        <th scope="col" class="px-5 py-3">Plan</th>
                        <th scope="col" class="px-5 py-3">Status</th>
                        <th scope="col" class="px-5 py-3 sm:px-6">Subscription ends</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($tenants as $tenant)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-3 font-medium text-slate-900 sm:px-6">
                                <a href="{{ route('admin.tenants.edit', $tenant) }}" class="hover:text-blue-700">{{ $tenant->company_name }}</a>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 capitalize text-slate-600">{{ $tenant->plan }}</td>
                            <td class="whitespace-nowrap px-5 py-3">
                                @php
                                    $statusStyle = match ($tenant->status) {
                                        'active', 'trial' => 'bg-emerald-50 text-emerald-700',
                                        'suspended' => 'bg-amber-50 text-amber-700',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp
                                <span @class(['inline-flex rounded-full px-2 py-1 text-xs font-medium capitalize', $statusStyle])>{{ $tenant->status }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-500 sm:px-6">{{ $tenant->subscription_ends_at?->format('M j, Y') ?? 'No end date' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center sm:px-6">
                                <p class="text-sm font-medium text-slate-800">No tenants yet</p>
                                <p class="mt-1 text-sm text-slate-500">Create the first tenant workspace to get started.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.dashboard>
