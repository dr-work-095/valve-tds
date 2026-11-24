<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateCompanySettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CompanySettingsController extends Controller
{
    public function edit(): View
    {
        $settings = DB::connection('tenant')->table('company_settings')->where('id', 1)->first();

        return view('tenant.settings.edit', [
            'settings' => $settings,
            'user' => Auth::guard('web')->user(),
            'tenant' => tenancy()->tenant,
        ]);
    }

    public function update(UpdateCompanySettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $tenant = tenancy()->tenant;
        $logoPath = null;

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store(
                $tenant->tenant_uid.'/logos',
                'tenant_private',
            );
        }

        $existing = DB::connection('tenant')->table('company_settings')->where('id', 1)->first();

        if ($logoPath === null) {
            $logoPath = $existing?->logo_path;
        } elseif ($existing?->logo_path) {
            Storage::disk('tenant_private')->delete($existing->logo_path);
        }

        DB::connection('tenant')->table('company_settings')->updateOrInsert(
            ['id' => 1],
            [
                'company_name' => $validated['company_name'],
                'address' => $validated['address'] ?? null,
                'tax_id_gst' => $validated['tax_id_gst'] ?? null,
                'logo_path' => $logoPath,
                'header_text' => $validated['header_text'] ?? null,
                'footer_text' => $validated['footer_text'] ?? null,
                'default_notes' => isset($validated['default_notes'])
                    ? json_encode(array_values($validated['default_notes']), JSON_THROW_ON_ERROR)
                    : null,
                'updated_at' => now(),
            ],
        );

        return redirect()->route('tenant.settings.edit')->with('status', 'Company settings updated.');
    }
}
