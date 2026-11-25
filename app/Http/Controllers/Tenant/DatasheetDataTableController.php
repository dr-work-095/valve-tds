<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\GeneratedDatasheetDataTableRequest;
use App\Models\GeneratedDatasheet;
use Illuminate\Http\JsonResponse;

class DatasheetDataTableController extends Controller
{
    public function __invoke(GeneratedDatasheetDataTableRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $query = GeneratedDatasheet::with('valveModel');
        $recordsTotal = (clone $query)->count();
        $search = trim((string) data_get($validated, 'search.value', ''));

        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query->where('document_number', 'like', '%'.$search.'%')
                    ->orWhere('client_name', 'like', '%'.$search.'%')
                    ->orWhere('project_reference', 'like', '%'.$search.'%');
            });
        }
        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $recordsFiltered = (clone $query)->count();
        $rows = $query->latest('id')
            ->skip((int) ($validated['start'] ?? 0))
            ->take((int) ($validated['length'] ?? 10))
            ->get();

        return response()->json([
            'draw' => (int) ($validated['draw'] ?? 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows->map(fn (GeneratedDatasheet $row): array => [
                'id' => $row->id,
                'document_number' => $row->document_number,
                'revision' => $row->revision,
                'client_name' => $row->client_name,
                'model_name' => $row->valveModel?->name,
                'status' => $row->status,
                'created_at' => $row->created_at?->format('Y-m-d H:i'),
                'revision_url' => route('tenant.datasheets.revision', $row),
                'download_url' => route('tenant.datasheets.download', $row),
                'retry_url' => route('tenant.datasheets.retry', $row),
            ]),
        ]);
    }
}
