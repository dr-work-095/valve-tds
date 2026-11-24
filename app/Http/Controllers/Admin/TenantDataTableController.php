<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TenantDataTableRequest;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;

class TenantDataTableController extends Controller
{
    private const ORDERABLE_COLUMNS = [
        0 => 'company_name',
        1 => 'plan',
        2 => 'status',
        3 => 'subscription_ends_at',
    ];

    public function __invoke(TenantDataTableRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $query = Tenant::on('central');
        $recordsTotal = (clone $query)->count();
        $search = trim((string) data_get($validated, 'search.value', ''));

        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query->where('company_name', 'like', '%'.$search.'%')
                    ->orWhere('tenant_uid', 'like', '%'.$search.'%')
                    ->orWhere('id', 'like', '%'.$search.'%');
            });
        }

        if (isset($validated['plan'])) {
            $query->where('plan', $validated['plan']);
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $recordsFiltered = (clone $query)->count();
        $orderColumn = (int) data_get($validated, 'order.0.column', 0);
        $orderDirection = data_get($validated, 'order.0.dir', 'asc');

        if (! isset(self::ORDERABLE_COLUMNS[$orderColumn])) {
            $orderColumn = 0;
        }

        $tenants = $query
            ->orderBy(self::ORDERABLE_COLUMNS[$orderColumn], $orderDirection)
            ->orderBy('id')
            ->skip((int) ($validated['start'] ?? 0))
            ->take((int) ($validated['length'] ?? 10))
            ->get();

        return response()->json([
            'draw' => (int) ($validated['draw'] ?? 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $tenants->map(fn (Tenant $tenant): array => [
                'id' => $tenant->tenant_uid,
                'company_name' => $tenant->company_name,
                'plan' => $tenant->plan,
                'status' => $tenant->status,
                'subscription_ends_at' => $tenant->subscription_ends_at?->format('M j, Y') ?? 'No end date',
                'provisioning_status' => $tenant->provisioningStatus(),
                'edit_url' => route('admin.tenants.edit', ['tenant' => $tenant->getKey()]),
            ]),
        ]);
    }
}
