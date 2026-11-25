<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Validation\ValidationException;

class TenantPlanLimits
{
    public function assertCanAddActiveUser(Tenant $tenant): void
    {
        $activeUserCount = $tenant->users()->where('is_active', true)->count();

        $this->assertWithinLimit($tenant, 'active_users', $activeUserCount);
    }

    public function assertWithinLimit(Tenant $tenant, string $resource, int $currentCount): void
    {
        $limit = $this->limit($tenant, $resource);

        if ($limit !== null && $currentCount >= $limit) {
            throw ValidationException::withMessages([
                $resource => "The {$tenant->plan} plan allows a maximum of {$limit} {$resource}.",
            ]);
        }
    }

    public function limit(Tenant $tenant, string $resource): ?int
    {
        $internalLimits = $tenant->getInternal('limits');
        $limits = is_array($internalLimits)
            ? $internalLimits
            : (array) data_get($tenant->data ?? [], 'limits', []);
        $value = data_get($limits, $resource);

        if ($value === null || $value === '') {
            return null;
        }

        return max(0, (int) $value);
    }

    public function setLimits(Tenant $tenant, array $limits): void
    {
        $data = (array) ($tenant->data ?? []);
        $normalize = static function (mixed $value): ?int {
            if ($value === null || $value === '') {
                return null;
            }

            return max(0, (int) $value);
        };
        $data['limits'] = [
            'active_users' => $normalize($limits['active_users_limit'] ?? null),
            'active_valve_models' => $normalize($limits['active_valve_models_limit'] ?? null),
            'monthly_pdfs' => $normalize($limits['monthly_pdfs_limit'] ?? null),
        ];
        $tenant->data = $data;
        $tenant->setInternal('limits', $data['limits']);
        $tenant->save();
    }
}
