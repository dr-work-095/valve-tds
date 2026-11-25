<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreValveBomTemplateRequest;
use App\Http\Requests\Tenant\UpdateValveBomTemplateRequest;
use App\Models\ValveBomTemplate;
use App\Models\ValveModel;
use Illuminate\Http\RedirectResponse;

class ValveBomTemplateController extends Controller
{
    public function index(int $valveModel): RedirectResponse
    {
        $model = ValveModel::findOrFail($valveModel);

        return redirect()->route('tenant.valve-models.edit', $model)->withFragment('bom');
    }

    public function store(StoreValveBomTemplateRequest $request, int $valveModel): RedirectResponse
    {
        $model = ValveModel::findOrFail($valveModel);
        ValveBomTemplate::create([...$request->validated(), 'valve_model_id' => $model->id]);

        return redirect()->route('tenant.valve-models.edit', $model)
            ->with('status', 'BOM row created.')
            ->withFragment('bom');
    }

    public function update(UpdateValveBomTemplateRequest $request, int $valveModel, int $bom): RedirectResponse
    {
        ValveModel::findOrFail($valveModel);
        $bom = ValveBomTemplate::where('valve_model_id', $valveModel)->findOrFail($bom);
        $bom->update($request->validated());

        return redirect()->route('tenant.valve-models.edit', $valveModel)
            ->with('status', 'BOM row updated.')
            ->withFragment('bom');
    }

    public function destroy(int $valveModel, int $bom): RedirectResponse
    {
        ValveModel::findOrFail($valveModel);
        ValveBomTemplate::where('valve_model_id', $valveModel)->findOrFail($bom)->delete();

        return redirect()->route('tenant.valve-models.edit', $valveModel)
            ->with('status', 'BOM row deleted.')
            ->withFragment('bom');
    }
}
