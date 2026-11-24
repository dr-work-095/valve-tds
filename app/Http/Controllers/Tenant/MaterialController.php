<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreMaterialRequest;
use App\Http\Requests\Tenant\UpdateMaterialRequest;
use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
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
        $material->update($request->validated());

        return redirect()->route('tenant.materials.index')->with('status', 'Material updated.');
    }
}
