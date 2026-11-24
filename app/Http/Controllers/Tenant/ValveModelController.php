<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreValveModelRequest;
use App\Http\Requests\Tenant\UpdateValveModelRequest;
use App\Models\ValveModel;
use App\Support\TenantPlanLimits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ValveModelController extends Controller
{
    public function index(): View
    {
        return view('tenant.valve-models.index', [
            'user' => Auth::guard('web')->user(),
            'tenant' => tenancy()->tenant,
        ]);
    }

    public function create(): View
    {
        return view('tenant.valve-models.create', [
            'user' => Auth::guard('web')->user(),
            'tenant' => tenancy()->tenant,
        ]);
    }

    public function store(StoreValveModelRequest $request, TenantPlanLimits $planLimits): RedirectResponse
    {
        $tenant = tenancy()->tenant;
        $validated = $request->validated();

        if ($request->boolean('is_active')) {
            $planLimits->assertWithinLimit(
                $tenant,
                'active_valve_models',
                ValveModel::where('is_active', true)->count(),
            );
        }

        $schematicPath = null;
        if ($request->hasFile('schematic')) {
            $schematicPath = $request->file('schematic')->store(
                $tenant->tenant_uid.'/schematics',
                'tenant_private',
            );
        }

        $customAttributes = collect($validated['custom_attributes'] ?? [])
            ->mapWithKeys(fn (array $attribute): array => [$attribute['key'] => $attribute['value']])
            ->all();

        ValveModel::create([
            ...$validated,
            'schematic_image_path' => $schematicPath,
            'custom_attributes' => $customAttributes,
        ]);

        return redirect()->route('tenant.valve-models.index')->with('status', 'Valve model created.');
    }

    public function edit(int $valveModel): View
    {
        $valveModel = ValveModel::findOrFail($valveModel);

        return view('tenant.valve-models.edit', [
            'valveModel' => $valveModel,
            'user' => Auth::guard('web')->user(),
            'tenant' => tenancy()->tenant,
        ]);
    }

    public function update(UpdateValveModelRequest $request, int $valveModel, TenantPlanLimits $planLimits): RedirectResponse
    {
        $valveModel = ValveModel::findOrFail($valveModel);
        $tenant = tenancy()->tenant;
        $validated = $request->validated();

        if (! $valveModel->is_active && $request->boolean('is_active')) {
            $planLimits->assertWithinLimit(
                $tenant,
                'active_valve_models',
                ValveModel::where('is_active', true)->count(),
            );
        }

        $schematicPath = $valveModel->schematic_image_path;
        if ($request->hasFile('schematic')) {
            $newPath = $request->file('schematic')->store(
                $tenant->tenant_uid.'/schematics',
                'tenant_private',
            );
            if ($schematicPath) {
                Storage::disk('tenant_private')->delete($schematicPath);
            }
            $schematicPath = $newPath;
        }

        $customAttributes = collect($validated['custom_attributes'] ?? [])
            ->mapWithKeys(fn (array $attribute): array => [$attribute['key'] => $attribute['value']])
            ->all();

        $valveModel->update([
            ...$validated,
            'schematic_image_path' => $schematicPath,
            'custom_attributes' => $customAttributes,
        ]);

        return redirect()->route('tenant.valve-models.index')->with('status', 'Valve model updated.');
    }
}
