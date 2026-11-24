<x-layouts.dashboard :page-title="'Dashboard'" :admin-mode="false" :tenant="$tenant" :user="$user">    

    <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Workspace summary">
        <article class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm font-medium text-slate-500">Company</p>
            <p class="mt-2 truncate text-xl font-semibold tracking-tight text-slate-900">{{ $tenant->company_name }}</p>
            <p class="mt-1 text-xs text-slate-500">Your isolated workspace</p>
        </article>
        <article class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm font-medium text-slate-500">Your role</p>
            <p class="mt-2 text-xl font-semibold capitalize tracking-tight text-slate-900">{{ $user->role }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $user->email }}</p>
        </article>
        <article class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm font-medium text-slate-500">Plan</p>
            <p class="mt-2 text-xl font-semibold capitalize tracking-tight text-slate-900">{{ $tenant->plan }}</p>
            <p class="mt-1 text-xs text-slate-500">
                {{ $tenant->subscription_ends_at ? 'Until '.$tenant->subscription_ends_at->format('M j, Y') : 'No scheduled end date' }}
            </p>
        </article>
        <article class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm font-medium text-slate-500">Workspace status</p>
            <p class="mt-2 text-xl font-semibold tracking-tight text-emerald-700">Connected</p>
            <p class="mt-1 text-xs text-slate-500">Tenant database verified for this request</p>
        </article>
    </section>

    <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
            <h2 class="text-base font-semibold text-slate-900">Workspace setup</h2>
            <p class="mt-1 text-sm text-slate-500">Your technical catalog and datasheet tools will appear here as they are configured.</p>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach ([
                ['1', 'Valve models and dimensions', "Define your company's valve models, ratings, and dimensional standards."],
                ['2', 'Materials and bill of materials', 'Build reusable material catalogs and valve component templates.'],
                ['3', 'Technical datasheets', 'Configure, revise, and export customer datasheets.'],
            ] as [$step, $title, $description])
                <div class="flex items-start gap-4 px-5 py-4 sm:px-6">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-xs font-semibold text-slate-500">{{ $step }}</span>
                    <div class="min-w-0">
                        <h3 class="text-sm font-medium text-slate-800">{{ $title }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
                    </div>
                    <span class="ml-auto shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">Next phase</span>
                </div>
            @endforeach
        </div>
    </section>
</x-layouts.dashboard>
