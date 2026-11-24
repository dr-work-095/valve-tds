<x-layouts.dashboard :page-title="'Manage tenant'" :admin-mode="true" :tenant="null" :user="auth('admin')->user()">
    <div class="mb-6">
        <a href="{{ route('admin.tenants.index') }}" class="text-sm font-medium text-blue-700 hover:text-blue-900">← Tenants</a>
        <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-900">{{ $tenant->company_name }}</h1>
        <p class="mt-1 text-sm text-slate-500">Tenant UID: <span class="font-mono">{{ $tenant->tenant_uid }}</span></p>
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

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(18rem,0.75fr)]">
        <form method="POST" action="{{ route('admin.tenants.update', $tenant) }}" class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
            @csrf
            @method('PUT')
            <h2 class="text-base font-semibold text-slate-900">Subscription and account</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="company_name" class="mb-1 block text-sm font-medium text-slate-700">Company name</label>
                    <input id="company_name" name="company_name" value="{{ old('company_name', $tenant->company_name) }}" required maxlength="255"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @error('company_name') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="plan" class="mb-1 block text-sm font-medium text-slate-700">Plan</label>
                    <select id="plan" name="plan" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($plans as $plan)
                            <option value="{{ $plan }}" @selected(old('plan', $tenant->plan) === $plan)>{{ ucfirst($plan) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="status" class="mb-1 block text-sm font-medium text-slate-700">Status</label>
                    <select id="status" name="status" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status', $tenant->status) === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label for="subscription_ends_at" class="mb-1 block text-sm font-medium text-slate-700">Subscription ends</label>
                    <input id="subscription_ends_at" name="subscription_ends_at" type="datetime-local"
                        value="{{ old('subscription_ends_at', $tenant->subscription_ends_at?->format('Y-m-d\TH:i')) }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @error('subscription_ends_at') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="payment_note" class="mb-1 block text-sm font-medium text-slate-700">Append payment or renewal note</label>
                    <textarea id="payment_note" name="payment_note" rows="3" maxlength="2000"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('payment_note') }}</textarea>
                    @error('payment_note') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
            </div>
            <button type="submit" class="mt-5 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Save subscription</button>
        </form>

        <div class="space-y-6">
            <section class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
                <h2 class="text-base font-semibold text-slate-900">Database provisioning</h2>
                <p class="mt-3 text-sm text-slate-600">Status: <span class="font-semibold capitalize">{{ $tenant->provisioningStatus() }}</span></p>
                @if ($tenant->provisioningError())
                    <p class="mt-2 text-sm text-red-700">{{ $tenant->provisioningError() }}</p>
                @endif
                @if ($tenant->provisioningStatus() === 'failed')
                    <form method="POST" action="{{ route('admin.tenants.provisioning.retry', $tenant) }}" class="mt-4">
                        @csrf
                        <button type="submit" class="rounded-lg border border-amber-300 px-4 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-50">Retry provisioning</button>
                    </form>
                @endif
            </section>
            <section class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
                <h2 class="text-base font-semibold text-slate-900">Admin notes and history</h2>
                <pre class="mt-3 whitespace-pre-wrap break-words font-sans text-sm text-slate-600">{{ $tenant->admin_notes ?: 'No notes recorded.' }}</pre>
            </section>
        </div>
    </div>
</x-layouts.dashboard>
