<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTenantRequest;
use App\Http\Requests\Admin\UpdateTenantRequest;
use App\Models\Tenant;
use App\Support\TenantPlanLimits;
use App\Support\TenantProvisioningDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use LogicException;

class TenantController extends Controller
{
    public function index(): View
    {
        return view('admin.tenants.index', [
            'plans' => Tenant::PLANS,
        ]);
    }

    public function create(): View
    {
        return view('admin.tenants.create', [
            'plans' => Tenant::PLANS,
            'statuses' => ['trial', 'active', 'suspended'],
        ]);
    }

    public function store(
        StoreTenantRequest $request,
        TenantPlanLimits $planLimits,
        TenantProvisioningDispatcher $provisioningDispatcher,
    ): RedirectResponse {
        $validated = $request->validated();

        $tenant = DB::connection('central')->transaction(function () use ($validated, $planLimits): Tenant {
            $tenant = Tenant::create([
                'company_name' => $validated['company_name'],
                'plan' => $validated['plan'],
                'status' => $validated['status'],
                'subscription_ends_at' => $validated['subscription_ends_at'] ?? null,
                'admin_notes' => $validated['admin_notes'] ?? null,
            ]);

            if (! preg_match('/\Atenant_[a-z0-9]{1,57}\z/', (string) $tenant->tenant_uid)) {
                throw new LogicException('The generated tenant UID does not match the canonical format.');
            }

            $databaseName = $tenant->database()->getName();

            if (! preg_match('/\Atds_tenant_[a-z0-9]{1,57}\z/', (string) $databaseName)) {
                throw new LogicException('The generated tenant database name is invalid: '.$databaseName);
            }

            $tenant->setInternal('db_name', $databaseName);
            $tenant->setInternal('provisioning_status', 'pending');
            $tenant->setInternal('provisioning_error', null);
            $tenant->save();

            $planLimits->setLimits($tenant, $validated);

            $planLimits->assertCanAddActiveUser($tenant);

            $tenant->users()->create([
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'password' => $validated['temporary_password'],
                'role' => 'admin',
                'is_active' => true,
            ]);

            return $tenant;
        });

        if (! $provisioningDispatcher->dispatch($tenant)) {
            return redirect()->route('admin.tenants.edit', $tenant)->withErrors([
                'provisioning' => 'The tenant and Company Admin were saved, but provisioning could not be queued. Retry provisioning from this page.',
            ]);
        }

        return redirect()->route('admin.tenants.edit', $tenant)->with(
            'status',
            'Tenant and Company Admin created. Database provisioning is queued.',
        );
    }

    public function edit(Tenant $tenant): View
    {
        return view('admin.tenants.edit', [
            'tenant' => $tenant,
            'plans' => Tenant::PLANS,
            'statuses' => Tenant::STATUSES,
        ]);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validated();

        DB::connection('central')->transaction(function () use ($tenant, $validated): void {
            $lockedTenant = Tenant::on('central')->whereKey($tenant->getKey())->lockForUpdate()->firstOrFail();
            $lockedTenant->fill(Arr::only($validated, [
                'company_name',
                'plan',
                'status',
                'subscription_ends_at',
            ]));

            $paymentNote = trim((string) ($validated['payment_note'] ?? ''));

            if ($paymentNote !== '') {
                $admin = Auth::guard('admin')->user();
                $entry = sprintf('[%s] %s: %s', now()->format('Y-m-d H:i:s'), $admin->name, $paymentNote);
                $existingNotes = trim((string) $lockedTenant->admin_notes);

                $lockedTenant->admin_notes = $existingNotes === ''
                    ? $entry
                    : $existingNotes.PHP_EOL.PHP_EOL.$entry;
            }

            $lockedTenant->save();

            app(TenantPlanLimits::class)->setLimits($lockedTenant, $validated);
        });

        return redirect()->route('admin.tenants.edit', $tenant)->with('status', 'Tenant subscription updated.');
    }

    public function retryProvisioning(
        Tenant $tenant,
        TenantProvisioningDispatcher $provisioningDispatcher,
    ): RedirectResponse {
        $lockedTenant = DB::connection('central')->transaction(function () use ($tenant): ?Tenant {
            $lockedTenant = Tenant::on('central')->whereKey($tenant->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedTenant->provisioningStatus() !== 'failed') {
                return null;
            }

            $lockedTenant->setInternal('provisioning_status', 'pending');
            $lockedTenant->setInternal('provisioning_error', null);
            $lockedTenant->save();

            return $lockedTenant;
        });

        if ($lockedTenant === null) {
            return back()->withErrors([
                'provisioning' => 'Only failed tenant provisioning can be retried.',
            ]);
        }

        if (! $provisioningDispatcher->dispatch($lockedTenant)) {
            return back()->withErrors([
                'provisioning' => 'Provisioning could not be queued. Check application logs and retry.',
            ]);
        }

        return back()->with('status', 'Tenant provisioning retry is queued.');
    }
}
