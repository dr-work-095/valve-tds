<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\DatasheetAccessory;
use App\Enums\DatasheetCompliance;
use App\Enums\FireSafeTestStandard;
use App\Enums\ValveCategory;
use App\Enums\ValveType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PrepareGeneratedDatasheetRequest;
use App\Http\Requests\Tenant\StoreGeneratedDatasheetRequest;
use App\Jobs\GenerateDatasheetPdfJob;
use App\Models\GeneratedDatasheet;
use App\Models\Material;
use App\Models\ValveDimension;
use App\Models\ValveModel;
use App\Support\DatasheetSnapshotBuilder;
use App\Support\TenantPlanLimits;
use App\Support\ValveModelReadiness;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatasheetController extends Controller
{
    public function index(): View
    {
        return view('tenant.datasheets.index', [
            'user' => Auth::guard('web')->user(),
            'tenant' => tenancy()->tenant,
        ]);
    }

    public function create(): View
    {
        return view('tenant.datasheets.create', [
            'user' => Auth::guard('web')->user(),
            'tenant' => tenancy()->tenant,
            'models' => (new ValveModelReadiness)->selectableQuery()->orderBy('name')->get(),
        ]);
    }

    public function prepare(PrepareGeneratedDatasheetRequest $request): RedirectResponse
    {
        $draft = (string) Str::uuid();
        session()->put('tenant_datasheet_drafts.'.$draft, $request->validated());

        return redirect()->route('tenant.datasheets.editor', $draft);
    }

    public function editor(string $draft, DatasheetSnapshotBuilder $snapshotBuilder): View
    {
        $selection = session()->get('tenant_datasheet_drafts.'.$draft);
        abort_unless(is_array($selection), 404);

        $model = (new ValveModelReadiness)
            ->selectableQuery()
            ->findOrFail($selection['valve_model_id']);
        $dimension = $model->dimensions()->find($selection['dimension_id']);
        abort_unless($dimension, 404);

        $bomMaterials = [];
        $bomTemplates = $model->bomTemplates()
            ->with('standardMaterial')
            ->orderBy('part_number')
            ->get();

        foreach ($bomTemplates as $bomTemplate) {
            $allowedMaterialIds = array_map('intval', array_merge(
                [$bomTemplate->standard_material_id],
                (array) $bomTemplate->optional_material_ids,
            ));
            $allowedMaterials = Material::query()
                ->where('is_active', true)
                ->whereIn('id', $allowedMaterialIds)
                ->orderBy('code')
                ->get();
            $material = $allowedMaterials->firstWhere('id', (int) $bomTemplate->standard_material_id)
                ?? $allowedMaterials->first();

            if (! $material) {
                throw ValidationException::withMessages([
                    'valve_model_id' => "BOM part {$bomTemplate->part_number} has no active allowed material.",
                ]);
            }

            $bomMaterials[] = [
                'bom_template_id' => (int) $bomTemplate->id,
                'material_id' => (int) $material->id,
            ];
        }

        $company = (array) (DB::connection('tenant')->table('company_settings')->where('id', 1)->first() ?? []);
        $defaultNotes = json_decode((string) ($company['default_notes'] ?? '[]'), true);
        $notes = implode("\n", array_filter((array) $defaultNotes, 'is_string'));
        $datasheetFields = [
            'document_status' => 'Issued for Inquiry',
            'identification' => [],
            'process_conditions' => [
                'flow_rate' => ['units' => 'm³/h'],
                'inlet_pressure' => ['units' => 'bar(g)'],
                'outlet_pressure' => ['units' => 'bar(g)'],
                'operating_temperature' => ['units' => '°C'],
                'density' => ['units' => 'kg/m³'],
                'specific_gravity' => [],
                'molecular_weight' => ['units' => 'g/mol'],
                'viscosity' => ['units' => 'cP'],
                'vapor_pressure' => ['units' => 'bar(a)'],
            ],
            'mechanical' => [],
            'control_performance' => [],
            'actuation' => [],
            'testing_compliance' => [
                'shell_test_standard' => $model->testing_standard,
                'seat_test_standard' => $model->testing_standard,
                'seat_leakage_class' => $model->seat_leakage_class,
            ],
            'revision' => [
                'description' => 'Issued for Design / Inquiry',
            ],
        ];
        $snapshot = $snapshotBuilder->build(
            $model,
            $dimension,
            $bomMaterials,
            $company,
            [
                ...$selection,
                'bom_materials' => $bomMaterials,
                'notes' => $notes,
                'datasheet_fields' => $datasheetFields,
            ],
        );

        return view('tenant.datasheets.editor', [
            'user' => Auth::guard('web')->user(),
            'tenant' => tenancy()->tenant,
            'draft' => $draft,
            'selection' => $selection,
            'snapshot' => $snapshot,
            'bomSelections' => $bomMaterials,
            'accessories' => DatasheetAccessory::cases(),
            'complianceOptions' => DatasheetCompliance::cases(),
            'fireSafeTestStandards' => FireSafeTestStandard::cases(),
            'valveCategories' => ValveCategory::cases(),
            'valveTypes' => ValveType::cases(),
            'revisionDate' => now()->toDateString(),
        ]);
    }

    public function backToSetup(string $draft): RedirectResponse
    {
        $selection = session()->get('tenant_datasheet_drafts.'.$draft);
        abort_unless(is_array($selection), 404);

        session()->forget('tenant_datasheet_drafts.'.$draft);

        return redirect()->route('tenant.datasheets.create')->withInput($selection);
    }

    public function modelsLookup(): JsonResponse
    {
        $models = (new ValveModelReadiness)->selectableQuery()
            ->orderBy('name')
            ->get(['id', 'name', 'valve_category', 'valve_type']);

        return response()->json(['data' => $models]);
    }

    public function modelLookup(int $valveModel): JsonResponse
    {
        $model = (new ValveModelReadiness)->selectableQuery()->findOrFail($valveModel);

        return response()->json([
            'model' => $model->only(['id', 'name', 'valve_category', 'valve_type', 'design_standard', 'testing_standard']),
            'dimensions' => $model->dimensions()->orderBy('size_inch')->orderBy('pressure_class')->get(),
            'bom' => $model->bomTemplates()->with('standardMaterial')->orderBy('part_number')->get(),
        ]);
    }

    public function store(
        StoreGeneratedDatasheetRequest $request,
        TenantPlanLimits $planLimits,
        DatasheetSnapshotBuilder $snapshotBuilder,
    ): RedirectResponse {
        $tenant = tenancy()->tenant;
        $validated = $request->validated();
        $model = ValveModel::findOrFail($validated['valve_model_id']);
        $dimension = ValveDimension::find($validated['dimension_id']);
        if (! $dimension || $dimension->valve_model_id !== $model->id) {
            throw ValidationException::withMessages([
                'dimension_id' => 'The selected dimension does not belong to the valve model.',
            ]);
        }
        $company = (array) (DB::connection('tenant')->table('company_settings')->where('id', 1)->first() ?? []);

        $snapshot = $snapshotBuilder->build(
            $model,
            $dimension,
            $validated['bom_materials'],
            $company,
            $validated,
        );

        $datasheet = DB::connection('tenant')->transaction(function () use (
            $tenant,
            $planLimits,
            $validated,
            $snapshot,
        ): GeneratedDatasheet {
            $monthLimit = $planLimits->limit($tenant, 'monthly_pdfs');
            if ($monthLimit !== null) {
                $monthStart = now()->startOfMonth();
                $monthCount = GeneratedDatasheet::where('created_at', '>=', $monthStart)->lockForUpdate()->count();
                if ($monthCount >= $monthLimit) {
                    throw ValidationException::withMessages([
                        'monthly_pdfs' => "The tenant limit of {$monthLimit} datasheets per month has been reached.",
                    ]);
                }
            }

            $documentNumber = trim((string) ($validated['document_number'] ?? ''));
            if ($documentNumber === '') {
                $documentNumber = 'TDS-'.now()->format('Y-m').'-'.str_pad((string) (GeneratedDatasheet::count() + 1), 4, '0', STR_PAD_LEFT);
            }

            $base = $documentNumber;
            $suffix = 1;
            while (GeneratedDatasheet::where('document_number', $base)->where('revision', 'Rev 0')->exists()) {
                $base = $documentNumber.'-'.$suffix;
                $suffix++;
            }

            $datasheet = GeneratedDatasheet::create([
                'created_by_user_id' => (int) Auth::guard('web')->id(),
                'valve_model_id' => (int) $validated['valve_model_id'],
                'document_number' => $base,
                'revision' => 'Rev 0',
                'client_name' => $validated['client_name'],
                'project_reference' => $validated['project_reference'] ?? null,
                'selected_specs' => $snapshot,
                'status' => 'queued',
            ]);

            GenerateDatasheetPdfJob::dispatch(
                (int) $tenant->getKey(),
                (int) $datasheet->id,
            );

            return $datasheet;
        });

        if ($request->filled('draft_id')) {
            session()->forget('tenant_datasheet_drafts.'.$request->input('draft_id'));
        }

        return redirect()->route('tenant.datasheets.index')->with('status', 'Datasheet created.');
    }

    public function revision(int $datasheet, TenantPlanLimits $planLimits): RedirectResponse
    {
        $tenant = tenancy()->tenant;
        $source = GeneratedDatasheet::findOrFail($datasheet);
        if ($source->status !== 'generated') {
            throw ValidationException::withMessages([
                'status' => 'A revision can only be created from a generated datasheet.',
            ]);
        }
        $monthLimit = $planLimits->limit($tenant, 'monthly_pdfs');

        $revision = DB::connection('tenant')->transaction(function () use ($source, $monthLimit): GeneratedDatasheet {
            $locked = GeneratedDatasheet::whereKey($source->id)->lockForUpdate()->firstOrFail();
            if ($monthLimit !== null) {
                $monthCount = GeneratedDatasheet::where('created_at', '>=', now()->startOfMonth())->lockForUpdate()->count();
                if ($monthCount >= $monthLimit) {
                    throw ValidationException::withMessages([
                        'monthly_pdfs' => "The tenant limit of {$monthLimit} datasheets per month has been reached.",
                    ]);
                }
            }

            $rootId = $locked->parent_datasheet_id ?? $locked->id;
            $latest = GeneratedDatasheet::where('document_number', $locked->document_number)
                ->where(function ($query) use ($rootId): void {
                    $query->where('id', $rootId)->orWhere('parent_datasheet_id', $rootId);
                })
                ->lockForUpdate()
                ->get();

            $nextRevision = 1 + $latest
                ->map(fn (GeneratedDatasheet $row): int => (int) filter_var($row->revision, FILTER_SANITIZE_NUMBER_INT))
                ->max();

            return GeneratedDatasheet::create([
                ...$locked->only([
                    'created_by_user_id',
                    'valve_model_id',
                    'document_number',
                    'parent_datasheet_id',
                    'client_name',
                    'project_reference',
                    'selected_specs',
                ]),
                'parent_datasheet_id' => $rootId,
                'revision' => 'Rev '.$nextRevision,
                'status' => 'queued',
            ]);
        });

        GenerateDatasheetPdfJob::dispatch(
            (int) $tenant->getKey(),
            (int) $revision->id,
        );

        return redirect()->route('tenant.datasheets.index')->with('status', 'Revision '.$revision->revision.' created.');
    }

    public function download(int $datasheet): BinaryFileResponse
    {
        $datasheet = GeneratedDatasheet::findOrFail($datasheet);
        if ($datasheet->status !== 'generated' || ! $datasheet->pdf_path) {
            abort(404);
        }

        $tenantUid = (string) tenancy()->tenant->tenant_uid;
        $expectedPrefix = $tenantUid.'/datasheets/';
        if (! str_starts_with($datasheet->pdf_path, $expectedPrefix)) {
            abort(404);
        }
        if (! Storage::disk('tenant_private')->exists($datasheet->pdf_path)) {
            abort(404);
        }

        $absolutePath = Storage::disk('tenant_private')->path($datasheet->pdf_path);

        return response()->download($absolutePath, basename($datasheet->pdf_path), [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, no-cache',
        ]);
    }

    public function retry(int $datasheet): RedirectResponse
    {
        $datasheet = GeneratedDatasheet::findOrFail($datasheet);
        if ($datasheet->status !== 'failed') {
            return back()->withErrors(['status' => 'Only failed datasheets can be retried.']);
        }

        $datasheet->update([
            'status' => 'queued',
            'pdf_path' => null,
        ]);
        GenerateDatasheetPdfJob::dispatch(
            (int) tenancy()->tenant->getKey(),
            (int) $datasheet->id,
        );

        return redirect()->route('tenant.datasheets.index')->with('status', 'PDF generation retry queued.');
    }
}
