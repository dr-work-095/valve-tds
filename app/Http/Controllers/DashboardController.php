<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function tenant(): View
    {
        $user = Auth::guard('web')->user();
        $tenant = Tenant::on('central')->where('tenant_uid', $user->tenant_id)->firstOrFail();

        return view('dashboard.tenant', compact('tenant', 'user'));
    }

    public function admin(): View
    {
        $now = now();

        return view('dashboard.admin', [
            'totalTenants' => Tenant::on('central')->count(),
            'activeTenants' => Tenant::on('central')->where('status', 'active')->count(),
            'expiringTenants' => Tenant::on('central')
                ->whereIn('status', ['active', 'trial'])
                ->whereNotNull('subscription_ends_at')
                ->whereBetween('subscription_ends_at', [$now, $now->copy()->addDays(7)])
                ->count(),
            'tenants' => Tenant::on('central')->latest()->limit(5)->get(),
            'user' => Auth::guard('admin')->user(),
        ]);
    }
}
