<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CoreSetupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var list<string>
     */
    private array $tenantDatabaseFiles = [];

    protected function tearDown(): void
    {
        DB::purge('tenant');

        foreach ($this->tenantDatabaseFiles as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    public function test_admin_dashboard_uses_the_central_guard_without_initializing_tenancy(): void
    {
        $admin = Admin::create([
            'name' => 'Central Operator',
            'email' => 'operator@example.test',
            'password' => 'secure-password',
        ]);

        $response = $this->actingAs($admin, 'admin')->get('/admin/dashboard');

        $response->assertOk()->assertSee('Tenant overview');
        $this->assertFalse(tenancy()->initialized);
        $this->assertSame('central', DB::getDefaultConnection());
    }

    public function test_user_factory_creates_a_central_membership_and_tenant(): void
    {
        $user = User::factory()->create();

        $this->assertNotEmpty($user->tenant_id);
        $this->assertMatchesRegularExpression('/\Atenant_[a-z0-9]+\z/', $user->tenant_id);
        $this->assertSame($user->tenant_id, $user->tenant->tenant_uid);
        $this->assertSame('trial', $user->tenant->status);
    }

    public function test_numeric_tenant_primary_key_is_not_used_as_tenant_membership(): void
    {
        [$tenant, $user] = $this->createTenantAndUser();
        DB::statement('PRAGMA foreign_keys = OFF');
        $user->forceFill([
            'tenant_id' => (string) $tenant->getKey(),
        ])->save();
        DB::statement('PRAGMA foreign_keys = ON');

        $this->actingAs($user->fresh(), 'web')
            ->get('/dashboard')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertFalse(tenancy()->initialized);
        $this->assertSame('central', DB::getDefaultConnection());
    }

    public function test_tenant_dashboard_uses_only_the_authenticated_users_tenant_and_resets_context(): void
    {
        [$tenant, $user] = $this->createTenantAndUser();

        $this->assertTrue($user->is_active);
        $this->assertSame($tenant->tenant_uid, $user->tenant_id);

        $response = $this->actingAs($user, 'web')->get('/dashboard?tenant_id=untrusted-tenant');

        $response->assertOk()
            ->assertSee('North Plant')
            ->assertSee('Your tenant workspace is connected');
        $this->assertFalse(tenancy()->initialized);
        $this->assertSame('central', DB::getDefaultConnection());
        $this->assertSame('tds_'.$tenant->tenant_uid, $tenant->database()->getName());
    }

    public function test_overdue_subscription_is_marked_expired_and_blocked(): void
    {
        [$tenant, $user] = $this->createTenantAndUser([
            'subscription_ends_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($user, 'web')->get('/dashboard');

        $response->assertForbidden()->assertSee('Subscription inactive');
        $this->assertSame('expired', $tenant->fresh()->status);
        $this->assertFalse(tenancy()->initialized);
    }

    public function test_null_subscription_end_date_does_not_expire_an_active_tenant(): void
    {
        [, $user] = $this->createTenantAndUser([
            'status' => 'trial',
            'subscription_ends_at' => null,
        ]);

        $this->actingAs($user, 'web')->get('/dashboard')->assertOk();
        $this->assertFalse(tenancy()->initialized);
    }

    public function test_suspended_tenant_is_blocked_before_tenant_initialization(): void
    {
        [, $user] = $this->createTenantAndUser(['status' => 'suspended']);

        $this->actingAs($user, 'web')->get('/dashboard')
            ->assertForbidden()
            ->assertSee('Subscription inactive');

        $this->assertFalse(tenancy()->initialized);
    }

    public function test_unavailable_tenant_database_returns_a_logged_service_error(): void
    {
        [$tenant, $user] = $this->createTenantAndUser();
        $databasePath = database_path($tenant->database()->getName());
        unlink($databasePath);

        $this->actingAs($user, 'web')->get('/dashboard')
            ->assertServiceUnavailable()
            ->assertSee('Workspace temporarily unavailable');

        $this->assertFalse(tenancy()->initialized);
        $this->assertSame('central', DB::getDefaultConnection());
    }

    public function test_inactive_accounts_cannot_authenticate_on_either_guard(): void
    {
        [$tenant] = $this->createTenantAndUser();
        $inactiveUser = User::create([
            'tenant_id' => $tenant->tenant_uid,
            'name' => 'Inactive User',
            'email' => 'inactive@example.test',
            'password' => 'secure-password',
            'is_active' => false,
        ]);
        $inactiveAdmin = Admin::create([
            'name' => 'Inactive Admin',
            'email' => 'inactive-admin@example.test',
            'password' => 'secure-password',
            'is_active' => false,
        ]);

        $this->post('/login', [
            'email' => $inactiveUser->email,
            'password' => 'secure-password',
        ])->assertSessionHasErrors('email');
        $this->assertFalse(Auth::guard('web')->check());

        $this->post('/admin/login', [
            'email' => $inactiveAdmin->email,
            'password' => 'secure-password',
        ])->assertSessionHasErrors('email');
        $this->assertFalse(Auth::guard('admin')->check());
    }

    public function test_admin_login_uses_only_the_admin_provider(): void
    {
        [$tenant] = $this->createTenantAndUser();
        $admin = Admin::create([
            'name' => 'Central Operator',
            'email' => 'shared@example.test',
            'password' => 'secure-password',
        ]);
        User::create([
            'tenant_id' => $tenant->tenant_uid,
            'name' => 'Tenant User',
            'email' => $admin->email,
            'password' => 'a-different-password',
        ]);

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'secure-password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertTrue(Auth::guard('admin')->check());
        $this->assertFalse(Auth::guard('web')->check());
        $this->assertFalse(tenancy()->initialized);
    }

    public function test_admin_login_ignores_a_stale_tenant_dashboard_redirect(): void
    {
        $admin = Admin::create([
            'name' => 'Central Operator',
            'email' => 'operator@example.test',
            'password' => 'secure-password',
        ]);

        $response = $this->withSession([
            'url.intended' => route('dashboard'),
        ])->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'secure-password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertTrue(Auth::guard('admin')->check());
        $this->assertFalse(session()->has('url.intended'));
    }

    /**
     * @param  array<string, mixed>  $tenantAttributes
     * @return array{Tenant, User}
     */
    private function createTenantAndUser(array $tenantAttributes = []): array
    {
        $tenant = Tenant::create(array_merge([
            'company_name' => 'North Plant',
            'plan' => 'starter',
            'status' => 'active',
        ], $tenantAttributes));

        $databasePath = database_path($tenant->database()->getName());
        $this->tenantDatabaseFiles[] = $databasePath;
        file_put_contents($databasePath, '');

        $user = User::create([
            'tenant_id' => $tenant->tenant_uid,
            'name' => 'Workspace User',
            'email' => 'user-'.str_replace(['tenant_', '-'], ['', ''], $tenant->tenant_uid).'@example.test',
            'password' => Hash::make('secure-password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        return [$tenant, $user];
    }
}
