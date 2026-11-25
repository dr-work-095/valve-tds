<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreMaterialRequest;
use App\Http\Requests\Tenant\UpdateMaterialRequest;
use App\Models\Material;
use App\Models\ValveBomTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MaterialController extends Controller
{
    public function index(): View
    {
        return view('tenant.materials.index', [
            'user' => Auth::guard('web')->user(),
            'tenant' => tenancy()->tenant,
        ]);
    }

    public function create(): View
    {
        return view('tenant.materials.create', [
            'user' => Auth::guard('web')->user(),
            'tenant' => tenancy()->tenant,
        ]);
    }

    public function store(StoreMaterialRequest $request): RedirectResponse
    {
        Material::create($request->validated());

        return redirect()->route('tenant.materials.index')->with('status', 'Material created.');
    }

    public function edit(int $material): View
    {
        $material = Material::findOrFail($material);

        return view('tenant.materials.edit', [
            'material' => $material,
            'user' => Auth::guard('web')->user(),
            'tenant' => tenancy()->tenant,
        ]);
    }

    public function update(UpdateMaterialRequest $request, int $material): RedirectResponse
    {
        $material = Material::findOrFail($material);
        if (! $request->boolean('is_active')) {
            $referenced = ValveBomTemplate::where('standard_material_id', $material->id)->exists();
            if (! $referenced) {
                $referenced = ValveBomTemplate::query()
                    ->get()
                    ->contains(fn (ValveBomTemplate $bom): bool => in_array(
                        (int) $material->id,
                        array_map('intval', (array) $bom->optional_material_ids),
                        true,
                    ));
            }
            if ($referenced) {
                throw ValidationException::withMessages([
                    'is_active' => 'This material is referenced by a BOM template. It can only be deactivated after removing those references.',
                ]);
            }
        }
        $material->update($request->validated());

        return redirect()->route('tenant.materials.index')->with('status', 'Material updated.');
    }
}
