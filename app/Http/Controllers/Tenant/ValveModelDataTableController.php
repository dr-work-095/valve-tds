<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ValveModelDataTableRequest;
use App\Models\ValveModel;
use App\Support\ValveModelReadiness;
use Illuminate\Http\JsonResponse;

class ValveModelDataTableController extends Controller
{
    private const COLUMNS = [0 => 'name', 1 => 'category', 2 => 'design_standard', 3 => 'testing_standard', 4 => 'is_active'];

    public function __invoke(ValveModelDataTableRequest $request, ValveModelReadiness $readiness): JsonResponse
    {
        $validated = $request->validated();
        $query = ValveModel::query();
        $recordsTotal = (clone $query)->count();
        $search = trim((string) data_get($validated, 'search.value', ''));

        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('category', 'like', '%'.$search.'%')
                    ->orWhere('design_standard', 'like', '%'.$search.'%')
                    ->orWhere('testing_standard', 'like', '%'.$search.'%');
            });
        }
        if (isset($validated['category'])) {
            $query->where('category', $validated['category']);
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
                'category' => $model->category,
                'design_standard' => $model->design_standard,
                'testing_standard' => $model->testing_standard,
                'is_active' => $model->is_active,
                'readiness_missing' => $readiness->missingRequirements($model),
                'edit_url' => route('tenant.valve-models.edit', $model),
            ]),
        ]);
    }
}
