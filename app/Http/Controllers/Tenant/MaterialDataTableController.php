<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\MaterialDataTableRequest;
use App\Models\Material;
use Illuminate\Http\JsonResponse;

class MaterialDataTableController extends Controller
{
    private const COLUMNS = [0 => 'code', 1 => 'name', 2 => 'category', 3 => 'is_active'];

    public function __invoke(MaterialDataTableRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $query = Material::query();
        $recordsTotal = (clone $query)->count();
        $search = trim((string) data_get($validated, 'search.value', ''));

        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query->where('code', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->orWhere('category', 'like', '%'.$search.'%');
            });
        }
        if (isset($validated['category'])) {
            $query->where('category', $validated['category']);
        }

        $recordsFiltered = (clone $query)->count();
        $orderColumn = (int) data_get($validated, 'order.0.column', 0);
        $orderDirection = data_get($validated, 'order.0.dir', 'asc');
        $orderColumn = self::COLUMNS[$orderColumn] ?? 'code';

        $materials = $query->orderBy($orderColumn, $orderDirection)->orderBy('id')
            ->skip((int) ($validated['start'] ?? 0))
            ->take((int) ($validated['length'] ?? 10))->get();

        return response()->json([
            'draw' => (int) ($validated['draw'] ?? 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $materials->map(fn (Material $material): array => [
                'id' => $material->getKey(),
                'code' => $material->code,
                'name' => $material->name,
                'category' => $material->category,
                'is_active' => $material->is_active,
                'edit_url' => route('tenant.materials.edit', $material),
            ]),
        ]);
    }
}
