<?php

namespace Tests\Feature;

use App\Jobs\ProvisionTenantDatabase;
use App\Models\Admin;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantPlanLimits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TenantManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_has_an_auto_increment_id_and_company_based_tenant_uid(): void
    {
        $firstTenant = Tenant::create([
            'company_name' => 'North & Plant #1',
            'plan' => 'starter',
            'status' => 'trial',
        ]);
        $secondTenant = Tenant::create([
            'company_name' => 'North & Plant #1',
            'plan' => 'starter',
            'status' => 'trial',
        ]);

        $this->assertIsInt($firstTenant->getKey());
        $this->assertGreaterThan(0, $firstTenant->getKey());
        $this->assertSame('tenant_northplant1', $firstTenant->tenant_uid);
        $this->assertSame('tenant_northplant12', $secondTenant->tenant_uid);
        $this->assertSame('tds_tenant_northplant1', $firstTenant->database()->getName());
    }

    public function test_duplicate_company_names_generate_unique_tenant_uids(): void
    {
        $firstTenant = Tenant::create([
            'company_name' => 'North Plant',
            'plan' => 'starter',
            'status' => 'trial',
        ]);
        $secondTenant = Tenant::create([
            'company_name' => 'North Plant',
            'plan' => 'starter',
            'status' => 'trial',
        ]);

        $this->assertSame('tenant_northplant', $firstTenant->tenant_uid);
        $this->assertSame('tenant_northplant2', $secondTenant->tenant_uid);
        $this->assertNotSame($firstTenant->getKey(), $secondTenant->getKey());
    }

    public function test_tenant_management_requires_an_active_admin_guard(): void
    {
        $inactiveAdmin = Admin::create([
            'name' => 'Inactive Operator',
            'email' => 'inactive-operator@example.test',
            'password' => 'secure-password',
            'is_active' => false,
        ]);

        $this->actingAs($inactiveAdmin, 'admin')
            ->get(route('admin.tenants.index'))
            ->assertRedirect(route('admin.login'));

        $this->assertFalse(Auth::guard('admin')->check());
    }

    public function test_admin_can_render_the_tenant_list_and_create_form(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')->get(route('admin.tenants.index'))
            ->assertOk()
            ->assertSee('Tenant management')
            ->assertSee('tenantsTable')
            ->assertSee('cdn.datatables.net/2.1.8/js/dataTables.min.js')
            ->assertSee('cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css');

        $this->get(route('admin.tenants.create'))
            ->assertOk()
            ->assertSee('Initial Company Admin')
            ->assertSee('temporary_password');
    }

    public function test_admin_can_create_tenant_and_company_admin_then_queue_provisioning(): void
    {
        $admin = $this->createAdmin();
        Queue::fake([ProvisionTenantDatabase::class]);

        $response = $this->actingAs($admin, 'admin')->post(route('admin.tenants.store'), [
            'company_name' => 'North Plant',
            'plan' => 'starter',
            'status' => 'trial',
            'subscription_ends_at' => '2027-01-15T12:00',
            'admin_notes' => 'Introduced by the engineering team.',
            'admin_name' => 'Plant Administrator',
            'admin_email' => 'plant-admin@example.test',
            'temporary_password' => 'first-login-password',
        ]);

        $tenant = Tenant::where('company_name', 'North Plant')->firstOrFail();
        $user = User::where('email', 'plant-admin@example.test')->firstOrFail();

        $response->assertRedirect(route('admin.tenants.edit', $tenant))
            ->assertSessionHas('status');
        $this->assertModelExists($tenant);
        $this->assertModelExists($user);
        $this->assertSame($tenant->tenant_uid, $user->tenant_id);
        $this->assertSame('admin', $user->role);
        $this->assertTrue(Hash::check('first-login-password', $user->password));
        $this->assertSame('pending', $tenant->provisioningStatus());
        Queue::assertPushed(ProvisionTenantDatabase::class, fn (ProvisionTenantDatabase $job): bool => $job->tenantId === $tenant->getKey());
    }

    public function test_invalid_tenant_creation_does_not_create_records_or_queue_provisioning(): void
    {
        $admin = $this->createAdmin();
        Queue::fake([ProvisionTenantDatabase::class]);

        $this->actingAs($admin, 'admin')->from(route('admin.tenants.create'))
            ->post(route('admin.tenants.store'), [
                'company_name' => '',
                'plan' => 'invalid',
                'status' => 'trial',
                'admin_name' => '',
                'admin_email' => 'not-an-email',
                'temporary_password' => 'short',
            ])
            ->assertSessionHasErrors(['company_name', 'plan', 'admin_name', 'admin_email', 'temporary_password']);

        $this->assertDatabaseCount('tenants', 0);
        $this->assertDatabaseCount('users', 0);
        Queue::assertNothingPushed();
    }

    public function test_admin_can_search_filter_and_paginate_tenants_safely(): void
    {
        $admin = $this->createAdmin();
        Tenant::create(['company_name' => 'North Plant', 'plan' => 'starter', 'status' => 'trial']);
        Tenant::create(['company_name' => 'South Plant', 'plan' => 'growth', 'status' => 'active']);

        $response = $this->actingAs($admin, 'admin')->getJson(route('admin.tenants.data', [
            'draw' => 3,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => 'North'],
            'plan' => 'starter',
            'status' => 'trial',
            'order' => [['column' => 0, 'dir' => 'asc']],
        ]));

        $response->assertOk()
            ->assertJsonPath('draw', 3)
            ->assertJsonPath('recordsTotal', 2)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.company_name', 'North Plant');
    }

    public function test_data_table_rejects_unallowlisted_ordering_fields(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'admin')->getJson(route('admin.tenants.data', [
            'order' => [['column' => 'company_name desc; drop table tenants', 'dir' => 'asc']],
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors('order.0.column');

        $this->assertDatabaseCount('tenants', 0);
    }

    public function test_tenant_update_appends_a_timestamped_admin_note(): void
    {
        $admin = $this->createAdmin();
        $tenant = Tenant::create([
            'company_name' => 'North Plant',
            'plan' => 'starter',
            'status' => 'trial',
            'admin_notes' => 'Initial account created.',
        ]);

        $this->travelTo(now()->setDate(2026, 12, 1)->setTime(10, 30));

        $this->actingAs($admin, 'admin')->put(route('admin.tenants.update', $tenant), [
            'company_name' => 'North Plant',
            'plan' => 'growth',
            'status' => 'active',
            'subscription_ends_at' => '2027-12-01T10:30',
            'payment_note' => 'Invoice INV-102 paid.',
        ])->assertRedirect(route('admin.tenants.edit', $tenant));

        $tenant->refresh();
        $this->assertSame('growth', $tenant->plan);
        $this->assertSame('active', $tenant->status);
        $this->assertStringContainsString('Initial account created.', $tenant->admin_notes);
        $this->assertStringContainsString('2026-12-01 10:30:00', $tenant->admin_notes);
        $this->assertStringContainsString('Central Operator: Invoice INV-102 paid.', $tenant->admin_notes);
    }

    public function test_only_failed_provisioning_can_be_retried(): void
    {
        $admin = $this->createAdmin();
        $tenant = Tenant::create([
            'company_name' => 'North Plant',
            'plan' => 'starter',
            'status' => 'trial',
        ]);
        $tenant->setInternal('provisioning_status', 'ready');
        $tenant->save();
        Queue::fake([ProvisionTenantDatabase::class]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.tenants.provisioning.retry', $tenant))
            ->assertSessionHasErrors('provisioning');

        Queue::assertNothingPushed();
    }

    public function test_failed_provisioning_can_be_retried_and_clears_the_previous_error(): void
    {
        $admin = $this->createAdmin();
        $tenant = Tenant::create([
            'company_name' => 'North Plant',
            'plan' => 'starter',
            'status' => 'trial',
        ]);
        $tenant->setInternal('provisioning_status', 'failed');
        $tenant->setInternal('provisioning_error', 'Previous failure');
        $tenant->save();
        Queue::fake([ProvisionTenantDatabase::class]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.tenants.provisioning.retry', $tenant))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame('pending', $tenant->fresh()->provisioningStatus());
        $this->assertNull($tenant->fresh()->provisioningError());
        Queue::assertPushed(ProvisionTenantDatabase::class, fn (ProvisionTenantDatabase $job): bool => $job->tenantId === $tenant->getKey());
    }

    public function test_tenant_edit_view_escapes_company_names_and_admin_notes(): void
    {
        $admin = $this->createAdmin();
        $tenant = Tenant::create([
            'company_name' => '<script>alert(1)</script>',
            'plan' => 'starter',
            'status' => 'trial',
            'admin_notes' => '<img src=x onerror=alert(1)>',
        ]);

        $this->actingAs($admin, 'admin')->get(route('admin.tenants.edit', $tenant))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    public function test_starter_plan_rejects_an_additional_active_user_at_its_limit(): void
    {
        $tenant = Tenant::create([
            'company_name' => 'North Plant',
            'plan' => 'starter',
            'status' => 'trial',
        ]);
        $tenant->users()->createMany([
            ['name' => 'First User', 'email' => 'first@example.test', 'password' => 'secure-password', 'is_active' => true],
            ['name' => 'Second User', 'email' => 'second@example.test', 'password' => 'secure-password', 'is_active' => true],
        ]);

        expect(fn () => app(TenantPlanLimits::class)->assertCanAddActiveUser($tenant))
            ->toThrow(ValidationException::class, 'allows a maximum of 2 active_users');
    }

    private function createAdmin(): Admin
    {
        return Admin::create([
            'name' => 'Central Operator',
            'email' => 'operator@example.test',
            'password' => 'secure-password',
        ]);
    }
}
