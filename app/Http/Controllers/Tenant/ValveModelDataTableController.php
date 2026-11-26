<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ValveModelDataTableRequest;
use App\Models\ValveModel;
use App\Support\ValveModelReadiness;
use Illuminate\Http\JsonResponse;

class ValveModelDataTableController extends Controller
{
    private const COLUMNS = [
        0 => 'name',
        1 => 'valve_category',
        2 => 'valve_type',
        3 => 'design_standard',
        4 => 'testing_standard',
        5 => 'is_active',
    ];

    public function __invoke(ValveModelDataTableRequest $request, ValveModelReadiness $readiness): JsonResponse
    {
        $validated = $request->validated();
        $query = ValveModel::query();
        $recordsTotal = (clone $query)->count();
        $search = trim((string) data_get($validated, 'search.value', ''));

        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('valve_category', 'like', '%'.$search.'%')
                    ->orWhere('valve_type', 'like', '%'.$search.'%')
                    ->orWhere('design_standard', 'like', '%'.$search.'%')
                    ->orWhere('testing_standard', 'like', '%'.$search.'%');
            });
        }
        if (isset($validated['valve_category'])) {
            $query->where('valve_category', $validated['valve_category']);
        }

        $recordsFiltered = (clone $query)->count();
        $orderColumn = (int) data_get($validated, 'order.0.column', 0);
        $orderDirection = data_get($validated, 'order.0.dir', 'asc');
        $orderColumn = self::COLUMNS[$orderColumn] ?? 'name';

        $models = $query->orderBy($orderColumn, $orderDirection)->orderBy('id')
            ->skip((int) ($validated['start'] ?? 0))
            ->take((int) ($validated['length'] ?? 10))->get();

        return response()->json([
            'draw' => (int) ($validated['draw'] ?? 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $models->map(fn (ValveModel $model): array => [
                'id' => $model->getKey(),
                'name' => $model->name,
                'valve_category' => $model->valve_category,
                'valve_type' => $model->valve_type,
                'design_standard' => $model->design_standard,
                'testing_standard' => $model->testing_standard,
                'is_active' => $model->is_active,
                'readiness_missing' => $readiness->missingRequirements($model),
                'edit_url' => route('tenant.valve-models.edit', $model),
            ]),
        ]);
    }
}
