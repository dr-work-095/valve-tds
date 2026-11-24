<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class TenantPlanLimits
{
    /**
     * @var array<string, array<string, int|null>>
     */
    private const LIMITS = [
        'starter' => [
            'active_users' => 2,
            'active_valve_models' => 5,
            'monthly_pdfs' => 100,
        ],
        'growth' => [
            'active_users' => 8,
            'active_valve_models' => null,
            'monthly_pdfs' => null,
        ],
        'pro' => [
            'active_users' => 20,
            'active_valve_models' => null,
            'monthly_pdfs' => null,
        ],
    ];

    public function assertCanAddActiveUser(Tenant $tenant): void
    {
        $activeUserCount = $tenant->users()->where('is_active', true)->count();

        $this->assertWithinLimit($tenant, 'active_users', $activeUserCount);
    }

    public function assertWithinLimit(Tenant $tenant, string $resource, int $currentCount): void
    {
        $limit = self::LIMITS[$tenant->plan][$resource] ?? null;

        if (! array_key_exists($tenant->plan, self::LIMITS)
            || ! array_key_exists($resource, self::LIMITS[$tenant->plan])) {
            throw new InvalidArgumentException('The tenant plan or resource has no configured limit.');
        }

        if ($limit !== null && $currentCount >= $limit) {
            throw ValidationException::withMessages([
                $resource => "The {$tenant->plan} plan allows a maximum of {$limit} {$resource}.",
            ]);
        }
    }
}
