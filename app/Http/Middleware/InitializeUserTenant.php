<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\User;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Database\SQLiteDatabaseDoesNotExistException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDOException;
use Symfony\Component\HttpFoundation\Response;

class InitializeUserTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if (! $user instanceof User || ! $user->is_active || ! $user->tenant_id) {
            Log::notice('Tenant request rejected because the authenticated user has no active membership.', [
                'user_id' => $user?->getAuthIdentifier(),
            ]);

            return $this->rejectMembership($request);
        }

        $tenant = Tenant::on('central')->where('tenant_uid', $user->tenant_id)->first();

        if (! $tenant) {
            Log::notice('Tenant request rejected because the user tenant record is missing.', [
                'user_id' => $user->getKey(),
                'tenant_id' => $user->tenant_id,
            ]);

            return $this->rejectMembership($request);
        }

        if (in_array($tenant->status, ['active', 'trial'], true)
            && $tenant->subscription_ends_at?->isPast()) {
            $tenant->forceFill(['status' => 'expired'])->save();
        }

        if (! in_array($tenant->status, ['active', 'trial'], true)) {
            return response()->view('auth.subscription-inactive', [
                'companyName' => $tenant->company_name,
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            tenancy()->initialize($tenant);
            DB::connection('tenant')->getPdo();

            return $next($request);
        } catch (QueryException|SQLiteDatabaseDoesNotExistException|PDOException $exception) {
            Log::error('Tenant database is unavailable for an authenticated request.', [
                'tenant_id' => $tenant->getKey(),
                'user_id' => $user->getKey(),
                'exception' => $exception,
            ]);

            return response()->view('errors.tenant-unavailable', [], Response::HTTP_SERVICE_UNAVAILABLE);
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
        }
    }

    private function rejectMembership(Request $request): Response
    {
        Auth::guard('web')->logout();
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'email' => 'Your user account is not associated with an active tenant. Contact your administrator.',
        ]);
    }
}
