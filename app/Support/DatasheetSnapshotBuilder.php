<?php

namespace App\Support;

use App\Models\Material;
use App\Models\ValveBomTemplate;
use App\Models\ValveDimension;
use App\Models\ValveModel;
use Illuminate\Validation\ValidationException;

class DatasheetSnapshotBuilder
{
    public function build(
        ValveModel $model,
        ValveDimension $dimension,
        array $bomMaterials,
        array $company,
        array $input,
    ): array {
        if (! $model->isReady()) {
            throw ValidationException::withMessages([
                'valve_model_id' => 'The selected valve model is not ready.',
            ]);
        }
        if ($dimension->valve_model_id !== $model->id) {
            throw ValidationException::withMessages([
                'dimension_id' => 'The selected dimension does not belong to the valve model.',
            ]);
        }

        $bom = [];
        foreach ($bomMaterials as $selection) {
            $bomTemplate = ValveBomTemplate::with('standardMaterial')
                ->where('valve_model_id', $model->id)
                ->find($selection['bom_template_id'] ?? null);

            if (! $bomTemplate) {
                throw ValidationException::withMessages([
                    'bom_materials' => 'A selected BOM row does not belong to the valve model.',
                ]);
            }

            $allowedIds = array_map('intval', array_merge(
                [$bomTemplate->standard_material_id],
                (array) $bomTemplate->optional_material_ids,
            ));
            $materialId = (int) ($selection['material_id'] ?? 0);
            $material = Material::where('is_active', true)->find($materialId);

            if (! $material || ! in_array($materialId, $allowedIds, true)) {
                throw ValidationException::withMessages([
                    'bom_materials' => 'Selected materials must be active and allowed for their BOM row.',
                ]);
            }

            $bom[] = [
                'part_number' => $bomTemplate->part_number,
                'part_name' => $bomTemplate->part_name,
                'material_code' => $material->code,
                'material_name' => $material->name,
            ];
        }

        usort($bom, fn (array $left, array $right): int => $left['part_number'] <=> $right['part_number']);

        return [
            'company' => [
                'company_name' => $company['company_name'] ?? null,
                'address' => $company['address'] ?? null,
                'tax_id_gst' => $company['tax_id_gst'] ?? null,
                'header_text' => $company['header_text'] ?? null,
                'footer_text' => $company['footer_text'] ?? null,
                'logo_path' => $company['logo_path'] ?? null,
            ],
            'model' => [
                'id' => $model->id,
                'name' => $model->name,
                'valve_category' => $model->valve_category,
                'valve_type' => $model->valve_type,
                'design_standard' => $model->design_standard,
                'testing_standard' => $model->testing_standard,
                'face_to_face_standard' => $model->face_to_face_standard,
                'end_connection_standard' => $model->end_connection_standard,
                'custom_attributes' => $model->custom_attributes,
            ],
            'dimension' => [
                'id' => $dimension->id,
                'size_inch' => $dimension->size_inch,
                'size_dn' => $dimension->size_dn,
                'pressure_class' => $dimension->pressure_class,
                'end_connection' => $dimension->end_connection,
                'face_to_face_mm' => $dimension->face_to_face_mm,
                'end_to_end_mm' => $dimension->end_to_end_mm,
                'flange_od_mm' => $dimension->flange_od_mm,
                'center_to_top_mm' => $dimension->center_to_top_mm,
                'weight_kg' => $dimension->weight_kg,
                'extra_dimensions' => $dimension->extra_dimensions,
            ],
            'bom' => $bom,
            'client_name' => $input['client_name'],
            'project_reference' => $input['project_reference'] ?? null,
            'notes' => $input['notes'] ?? null,
        ];
    }
}
