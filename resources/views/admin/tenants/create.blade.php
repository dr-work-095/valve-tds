<x-layouts.dashboard :page-title="'Add tenant'" :admin-mode="true" :tenant="null" :user="auth('admin')->user()">
    <div class="p-4 sm:p-6 lg:p-8">
    <div class="mb-6">
        <a href="{{ route('admin.tenants.index') }}" class="text-sm font-medium text-blue-700 hover:text-blue-900">← Tenants</a>
        <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-900">Create tenant</h1>
        <p class="mt-1 text-sm text-slate-500">Create the central tenant record and its first Company Admin account.</p>
    </div>

    @if ($errors->any())
        <div role="alert" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.tenants.store') }}" class="space-y-6">
        @csrf
        <section>
            <h2 class="text-base font-semibold text-slate-900">Company and subscription</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="company_name" class="mb-1 block text-sm font-medium text-slate-700">Company name</label>
                    <input id="company_name" name="company_name" value="{{ old('company_name') }}" required maxlength="255"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @error('company_name') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="plan" class="mb-1 block text-sm font-medium text-slate-700">Plan</label>
                    <select id="plan" name="plan" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($plans as $plan)
                            <option value="{{ $plan }}" @selected(old('plan', 'starter') === $plan)>{{ ucfirst($plan) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="status" class="mb-1 block text-sm font-medium text-slate-700">Subscription status</label>
                    <select id="status" name="status" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status', 'trial') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="subscription_ends_at" class="mb-1 block text-sm font-medium text-slate-700">Subscription ends</label>
                    <input id="subscription_ends_at" name="subscription_ends_at" type="datetime-local"
                        value="{{ old('subscription_ends_at') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @error('subscription_ends_at') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="active_users_limit" class="mb-1 block text-sm font-medium text-slate-700">Active user limit</label>
                    <input id="active_users_limit" name="active_users_limit" type="number" min="0" value="{{ old('active_users_limit') }}" placeholder="Unlimited" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label for="active_valve_models_limit" class="mb-1 block text-sm font-medium text-slate-700">Active model limit</label>
                    <input id="active_valve_models_limit" name="active_valve_models_limit" type="number" min="0" value="{{ old('active_valve_models_limit') }}" placeholder="Unlimited" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label for="monthly_pdfs_limit" class="mb-1 block text-sm font-medium text-slate-700">Monthly datasheet limit</label>
                    <input id="monthly_pdfs_limit" name="monthly_pdfs_limit" type="number" min="0" value="{{ old('monthly_pdfs_limit') }}" placeholder="Unlimited" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label for="admin_notes" class="mb-1 block text-sm font-medium text-slate-700">Admin notes</label>
                    <textarea id="admin_notes" name="admin_notes" rows="3" maxlength="5000"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('admin_notes') }}</textarea>
                    @error('admin_notes') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section>
            <h2 class="text-base font-semibold text-slate-900">Initial Company Admin</h2>
            <p class="mt-1 text-sm text-slate-500">The temporary password must be at least 12 characters.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="admin_name" class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                    <input id="admin_name" name="admin_name" value="{{ old('admin_name') }}" required maxlength="255"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @error('admin_name') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="admin_email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                    <input id="admin_email" name="admin_email" type="email" value="{{ old('admin_email') }}" required maxlength="255"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @error('admin_email') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="temporary_password" class="mb-1 block text-sm font-medium text-slate-700">Temporary password</label>
                    <input id="temporary_password" name="temporary_password" type="password" required minlength="12" maxlength="255"
                        autocomplete="new-password" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @error('temporary_password') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('admin.tenants.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-center text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Create tenant</button>
        </div>
    </form>
    </div>
</x-layouts.dashboard>
