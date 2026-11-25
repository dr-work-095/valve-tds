<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreValveDimensionRequest;
use App\Http\Requests\Tenant\UpdateValveDimensionRequest;
use App\Models\ValveDimension;
use App\Models\ValveModel;
use Illuminate\Http\RedirectResponse;

class ValveDimensionController extends Controller
{
    public function index(int $valveModel): RedirectResponse
    {
        $model = ValveModel::findOrFail($valveModel);

        return redirect()->route('tenant.valve-models.edit', $model)->withFragment('dimensions');
    }

    public function store(StoreValveDimensionRequest $request, int $valveModel): RedirectResponse
    {
        $model = ValveModel::findOrFail($valveModel);
        $validated = $request->validated();
        $extraDimensions = collect($validated['extra_dimensions'] ?? [])
            ->mapWithKeys(fn (array $dimension): array => [$dimension['key'] => $dimension['value'] ?? null])
            ->all();

        ValveDimension::create([
            ...$validated,
            'valve_model_id' => $model->id,
            'extra_dimensions' => $extraDimensions,
        ]);

        return redirect()->route('tenant.valve-models.edit', $model)
            ->with('status', 'Dimension row created.')
            ->withFragment('dimensions');
    }

    public function update(UpdateValveDimensionRequest $request, int $valveModel, int $dimension): RedirectResponse
    {
        ValveModel::findOrFail($valveModel);
        $dimension = ValveDimension::where('valve_model_id', $valveModel)->findOrFail($dimension);
        $validated = $request->validated();
        $extraDimensions = collect($validated['extra_dimensions'] ?? [])
            ->mapWithKeys(fn (array $item): array => [$item['key'] => $item['value'] ?? null])
            ->all();

        $dimension->update([...$validated, 'extra_dimensions' => $extraDimensions]);

        return redirect()->route('tenant.valve-models.edit', $valveModel)
            ->with('status', 'Dimension row updated.')
            ->withFragment('dimensions');
    }

    public function destroy(int $valveModel, int $dimension): RedirectResponse
    {
        ValveModel::findOrFail($valveModel);
        ValveDimension::where('valve_model_id', $valveModel)->findOrFail($dimension)->delete();

        return redirect()->route('tenant.valve-models.edit', $valveModel)
            ->with('status', 'Dimension row deleted.')
            ->withFragment('dimensions');
    }
}
