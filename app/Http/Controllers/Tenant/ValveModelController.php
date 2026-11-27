<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\ValveCategory;
use App\Enums\ValveComponentGroup;
use App\Enums\ValveType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreValveModelRequest;
use App\Http\Requests\Tenant\UpdateValveModelRequest;
use App\Models\Material;
use App\Models\ValveBomTemplate;
use App\Models\ValveDimension;
use App\Models\ValveModel;
use App\Support\TenantPlanLimits;
use App\Support\ValveModelReadiness;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ValveModelController extends Controller
{
    public function index(ValveModelReadiness $readiness): View
    {
        return view('tenant.valve-models.index', [
            'user' => Auth::guard('web')->user(),
            'tenant' => tenancy()->tenant,
            'readiness' => $readiness,
        ]);
    }

    public function create(): View
    {
        return view('tenant.valve-models.create', [
            'user' => Auth::guard('web')->user(),
            'tenant' => tenancy()->tenant,
            'valveCategories' => ValveCategory::cases(),
            'valveTypes' => ValveType::cases(),
        ]);
    }

    public function show(int $valveModel): View
    {
        $valveModel = ValveModel::with([
            'dimensions' => fn (HasMany $query) => $query
                ->orderBy('size_inch')
                ->orderBy('pressure_class')
                ->orderBy('end_connection'),
            'bomTemplates' => fn (HasMany $query) => $query
                ->with('standardMaterial')
                ->orderBy('part_number'),
        ])->findOrFail($valveModel);

        $optionalMaterialIds = $valveModel->bomTemplates
            ->flatMap(fn (ValveBomTemplate $bom): array => (array) $bom->optional_material_ids)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        return view('tenant.valve-models.show', [
            'valveModel' => $valveModel,
            'user' => Auth::guard('web')->user(),
            'tenant' => tenancy()->tenant,
            'optionalMaterials' => Material::whereIn('id', $optionalMaterialIds)->get()->keyBy('id'),
            'schematicUrl' => filled($valveModel->schematic_image_path)
                ? route('tenant.media', [
                    'category' => 'schematics',
                    'path' => basename($valveModel->schematic_image_path),
                ])
                : null,
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

    public function edit(int $valveModel, ValveModelReadiness $readiness): View
    {
        $valveModel = ValveModel::findOrFail($valveModel);

        return view('tenant.valve-models.edit', [
            'valveModel' => $valveModel,
            'user' => Auth::guard('web')->user(),
            'tenant' => tenancy()->tenant,
            'readiness_missing' => $readiness->missingRequirements($valveModel),
            'dimensions' => ValveDimension::where('valve_model_id', $valveModel->id)->orderBy('size_inch')->get(),
            'bomRows' => ValveBomTemplate::with('standardMaterial')
                ->where('valve_model_id', $valveModel->id)
                ->orderBy('part_number')
                ->get(),
            'materials' => Material::where('is_active', true)->orderBy('code')->get(),
            'valveCategories' => ValveCategory::cases(),
            'componentGroups' => ValveComponentGroup::cases(),
            'valveTypes' => ValveType::cases(),
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
