<?php

namespace App\Http\Requests\Tenant;

use App\Enums\DatasheetAccessory;
use App\Enums\DatasheetCompliance;
use App\Enums\ValveCategory;
use App\Enums\ValveType;
use App\Models\ValveModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreGeneratedDatasheetRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $draft = $this->input('draft_id');
        if (! is_string($draft) || ! Str::isUuid($draft)) {
            return;
        }

        $selection = session()->get('tenant_datasheet_drafts.'.$draft);
        if (! is_array($selection)) {
            return;
        }

        $this->merge([
            'valve_model_id' => $selection['valve_model_id'],
            'dimension_id' => $selection['dimension_id'],
        ]);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'client_name' => ['required', 'string', 'max:255'],
            'project_reference' => ['nullable', 'string', 'max:255'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'valve_model_id' => ['required', 'integer', 'exists:tenant.valve_models,id'],
            'dimension_id' => ['required', 'integer', 'exists:tenant.valve_dimensions,id'],
            'draft_id' => ['nullable', 'uuid'],
            'bom_materials' => ['required', 'array', 'min:1'],
            'bom_materials.*.bom_template_id' => ['required', 'integer', 'exists:tenant.valve_bom_templates,id'],
            'bom_materials.*.material_id' => ['required', 'integer', 'exists:tenant.materials,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'snapshot_edits' => ['nullable', 'array:model,dimension,bom'],
            'snapshot_edits.model' => ['nullable', 'array:name,valve_category,valve_type,design_standard,testing_standard,face_to_face_standard,end_connection_standard,flange_facing_finish,bonnet_cover_type,bore_port_configuration,flow_characteristic,seat_leakage_class'],
            'snapshot_edits.model.name' => ['nullable', 'string', 'max:255'],
            'snapshot_edits.model.valve_category' => ['nullable', Rule::enum(ValveCategory::class)],
            'snapshot_edits.model.valve_type' => ['nullable', Rule::enum(ValveType::class)],
            'snapshot_edits.model.design_standard' => ['nullable', 'string', 'max:255'],
            'snapshot_edits.model.testing_standard' => ['nullable', 'string', 'max:255'],
            'snapshot_edits.model.face_to_face_standard' => ['nullable', 'string', 'max:255'],
            'snapshot_edits.model.end_connection_standard' => ['nullable', 'string', 'max:255'],
            'snapshot_edits.model.flange_facing_finish' => ['nullable', 'string', 'max:255'],
            'snapshot_edits.model.bonnet_cover_type' => ['nullable', 'string', 'max:255'],
            'snapshot_edits.model.bore_port_configuration' => ['nullable', 'string', 'max:255'],
            'snapshot_edits.model.flow_characteristic' => ['nullable', 'string', 'max:100'],
            'snapshot_edits.model.seat_leakage_class' => ['nullable', 'string', 'max:100'],
            'snapshot_edits.dimension' => ['nullable', 'array:size_inch,size_dn,pressure_class,end_connection,face_to_face_mm,end_to_end_mm,flange_od_mm,center_to_top_mm,weight_kg'],
            'snapshot_edits.dimension.size_inch' => ['nullable', 'string', 'max:50'],
            'snapshot_edits.dimension.size_dn' => ['nullable', 'string', 'max:50'],
            'snapshot_edits.dimension.pressure_class' => ['nullable', 'string', 'max:100'],
            'snapshot_edits.dimension.end_connection' => ['nullable', 'string', 'max:100'],
            'snapshot_edits.dimension.face_to_face_mm' => ['nullable', 'numeric', 'min:0'],
            'snapshot_edits.dimension.end_to_end_mm' => ['nullable', 'numeric', 'min:0'],
            'snapshot_edits.dimension.flange_od_mm' => ['nullable', 'numeric', 'min:0'],
            'snapshot_edits.dimension.center_to_top_mm' => ['nullable', 'numeric', 'min:0'],
            'snapshot_edits.dimension.weight_kg' => ['nullable', 'numeric', 'min:0'],
            'snapshot_edits.bom' => ['nullable', 'array'],
            'snapshot_edits.bom.*' => ['array:component_group,part_name,material_code,material_name,quantity_per_valve,unit,notes'],
            'snapshot_edits.bom.*.component_group' => ['nullable', 'string', 'max:100'],
            'snapshot_edits.bom.*.part_name' => ['nullable', 'string', 'max:255'],
            'snapshot_edits.bom.*.material_code' => ['nullable', 'string', 'max:100'],
            'snapshot_edits.bom.*.material_name' => ['nullable', 'string', 'max:255'],
            'snapshot_edits.bom.*.quantity_per_valve' => ['nullable', 'numeric', 'min:0'],
            'snapshot_edits.bom.*.unit' => ['nullable', 'string', 'max:50'],
            'snapshot_edits.bom.*.notes' => ['nullable', 'string', 'max:500'],
            'datasheet_fields' => ['nullable', 'array:document_status,identification,process_conditions,mechanical,control_performance,actuation,testing_compliance,revision'],
            'datasheet_fields.document_status' => ['nullable', 'string', 'max:100'],
            'datasheet_fields.identification' => ['nullable', 'array:equipment_tag,pid_drawing_number,service_fluid_name,piping_line_spec,quantity_required,installation_location'],
            'datasheet_fields.identification.equipment_tag' => ['nullable', 'string', 'max:100'],
            'datasheet_fields.identification.pid_drawing_number' => ['nullable', 'string', 'max:255'],
            'datasheet_fields.identification.service_fluid_name' => ['nullable', 'string', 'max:255'],
            'datasheet_fields.identification.piping_line_spec' => ['nullable', 'string', 'max:255'],
            'datasheet_fields.identification.quantity_required' => ['nullable', 'string', 'max:50'],
            'datasheet_fields.identification.installation_location' => ['nullable', 'string', 'max:255'],
            'datasheet_fields.process_conditions' => ['nullable', 'array:fluid_phase_state,flow_rate,inlet_pressure,outlet_pressure,operating_temperature,density,specific_gravity,molecular_weight,viscosity,vapor_pressure,design_pressure,design_temperature'],
            'datasheet_fields.process_conditions.fluid_phase_state' => ['nullable', 'string', 'max:255'],
            'datasheet_fields.process_conditions.flow_rate' => ['nullable', 'array:units,minimum,normal,maximum'],
            'datasheet_fields.process_conditions.flow_rate.units' => ['nullable', 'string', 'max:50'],
            'datasheet_fields.process_conditions.flow_rate.minimum' => ['nullable', 'numeric'],
            'datasheet_fields.process_conditions.flow_rate.normal' => ['nullable', 'numeric'],
            'datasheet_fields.process_conditions.flow_rate.maximum' => ['nullable', 'numeric'],
            'datasheet_fields.process_conditions.inlet_pressure' => ['nullable', 'array:units,minimum,normal,maximum'],
            'datasheet_fields.process_conditions.inlet_pressure.units' => ['nullable', 'string', 'max:50'],
            'datasheet_fields.process_conditions.inlet_pressure.minimum' => ['nullable', 'numeric'],
            'datasheet_fields.process_conditions.inlet_pressure.normal' => ['nullable', 'numeric'],
            'datasheet_fields.process_conditions.inlet_pressure.maximum' => ['nullable', 'numeric'],
            'datasheet_fields.process_conditions.outlet_pressure' => ['nullable', 'array:units,minimum,normal,maximum,shutoff_delta_p'],
            'datasheet_fields.process_conditions.outlet_pressure.units' => ['nullable', 'string', 'max:50'],
            'datasheet_fields.process_conditions.outlet_pressure.minimum' => ['nullable', 'numeric'],
            'datasheet_fields.process_conditions.outlet_pressure.normal' => ['nullable', 'numeric'],
            'datasheet_fields.process_conditions.outlet_pressure.maximum' => ['nullable', 'numeric'],
            'datasheet_fields.process_conditions.outlet_pressure.shutoff_delta_p' => ['nullable', 'numeric'],
            'datasheet_fields.process_conditions.operating_temperature' => ['nullable', 'array:units,minimum,normal,maximum'],
            'datasheet_fields.process_conditions.operating_temperature.units' => ['nullable', 'string', 'max:50'],
            'datasheet_fields.process_conditions.operating_temperature.minimum' => ['nullable', 'numeric'],
            'datasheet_fields.process_conditions.operating_temperature.normal' => ['nullable', 'numeric'],
            'datasheet_fields.process_conditions.operating_temperature.maximum' => ['nullable', 'numeric'],
            'datasheet_fields.process_conditions.density' => ['nullable', 'array:units,minimum,normal,maximum'],
            'datasheet_fields.process_conditions.density.units' => ['nullable', 'string', 'max:50'],
            'datasheet_fields.process_conditions.density.minimum' => ['nullable', 'numeric'],
            'datasheet_fields.process_conditions.density.normal' => ['nullable', 'numeric'],
            'datasheet_fields.process_conditions.density.maximum' => ['nullable', 'numeric'],
            'datasheet_fields.process_conditions.specific_gravity' => ['nullable', 'array:minimum,normal,maximum'],
            'datasheet_fields.process_conditions.specific_gravity.minimum' => ['nullable', 'numeric'],
            'datasheet_fields.process_conditions.specific_gravity.normal' => ['nullable', 'numeric'],
            'datasheet_fields.process_conditions.specific_gravity.maximum' => ['nullable', 'numeric'],
            'datasheet_fields.process_conditions.molecular_weight' => ['nullable', 'array:units,minimum,normal,maximum'],
            'datasheet_fields.process_conditions.molecular_weight.units' => ['nullable', 'string', 'max:50'],
            'datasheet_fields.process_conditions.molecular_weight.minimum' => ['nullable', 'numeric'],
            'datasheet_fields.process_conditions.molecular_weight.normal' => ['nullable', 'numeric'],
            'datasheet_fields.process_conditions.molecular_weight.maximum' => ['nullable', 'numeric'],
            'datasheet_fields.process_conditions.viscosity' => ['nullable', 'array:units,minimum,normal,maximum'],
            'datasheet_fields.process_conditions.viscosity.units' => ['nullable', 'string', 'max:50'],
            'datasheet_fields.process_conditions.viscosity.minimum' => ['nullable', 'numeric'],
            'datasheet_fields.process_conditions.viscosity.normal' => ['nullable', 'numeric'],
            'datasheet_fields.process_conditions.viscosity.maximum' => ['nullable', 'numeric'],
            'datasheet_fields.process_conditions.vapor_pressure' => ['nullable', 'array:units,minimum,normal,maximum'],
            'datasheet_fields.process_conditions.vapor_pressure.units' => ['nullable', 'string', 'max:50'],
            'datasheet_fields.process_conditions.vapor_pressure.minimum' => ['nullable', 'numeric'],
            'datasheet_fields.process_conditions.vapor_pressure.normal' => ['nullable', 'numeric'],
            'datasheet_fields.process_conditions.vapor_pressure.maximum' => ['nullable', 'numeric'],
            'datasheet_fields.process_conditions.design_pressure' => ['nullable', 'string', 'max:100'],
            'datasheet_fields.process_conditions.design_temperature' => ['nullable', 'string', 'max:100'],
            'datasheet_fields.mechanical' => ['nullable', 'array:line_size,nominal_size'],
            'datasheet_fields.mechanical.line_size' => ['nullable', 'string', 'max:100'],
            'datasheet_fields.mechanical.nominal_size' => ['nullable', 'string', 'max:255'],
            'datasheet_fields.control_performance' => ['nullable', 'array:valve_travel,required_capacity,predicted_noise,selected_rated_cv,selected_rated_kv,cavitation_status'],
            'datasheet_fields.control_performance.valve_travel' => ['nullable', 'array:minimum,normal,maximum'],
            'datasheet_fields.control_performance.valve_travel.minimum' => ['nullable', 'numeric'],
            'datasheet_fields.control_performance.valve_travel.normal' => ['nullable', 'numeric'],
            'datasheet_fields.control_performance.valve_travel.maximum' => ['nullable', 'numeric'],
            'datasheet_fields.control_performance.required_capacity' => ['nullable', 'array:units,minimum,normal,maximum'],
            'datasheet_fields.control_performance.required_capacity.units' => ['nullable', 'string', 'max:10'],
            'datasheet_fields.control_performance.required_capacity.minimum' => ['nullable', 'numeric'],
            'datasheet_fields.control_performance.required_capacity.normal' => ['nullable', 'numeric'],
            'datasheet_fields.control_performance.required_capacity.maximum' => ['nullable', 'numeric'],
            'datasheet_fields.control_performance.predicted_noise' => ['nullable', 'string', 'max:100'],
            'datasheet_fields.control_performance.selected_rated_cv' => ['nullable', 'numeric'],
            'datasheet_fields.control_performance.selected_rated_kv' => ['nullable', 'numeric'],
            'datasheet_fields.control_performance.cavitation_status' => ['nullable', 'string', 'max:255'],
            'datasheet_fields.actuation' => ['nullable', 'array:actuator_type,supply_rating,fail_safe_action,stroking_time,mounted_accessories'],
            'datasheet_fields.actuation.actuator_type' => ['nullable', 'string', 'max:255'],
            'datasheet_fields.actuation.supply_rating' => ['nullable', 'string', 'max:255'],
            'datasheet_fields.actuation.fail_safe_action' => ['nullable', 'string', 'max:255'],
            'datasheet_fields.actuation.stroking_time' => ['nullable', 'string', 'max:100'],
            'datasheet_fields.actuation.mounted_accessories' => ['nullable', 'array'],
            'datasheet_fields.actuation.mounted_accessories.*' => ['required', Rule::enum(DatasheetAccessory::class)],
            'datasheet_fields.testing_compliance' => ['nullable', 'array:hydrostatic_seat_test_standard,seat_leakage_class,material_certification,ndt_requirements,special_compliance,other_compliance'],
            'datasheet_fields.testing_compliance.hydrostatic_seat_test_standard' => ['nullable', 'string', 'max:255'],
            'datasheet_fields.testing_compliance.seat_leakage_class' => ['nullable', 'string', 'max:100'],
            'datasheet_fields.testing_compliance.material_certification' => ['nullable', 'string', 'max:100'],
            'datasheet_fields.testing_compliance.ndt_requirements' => ['nullable', 'string', 'max:500'],
            'datasheet_fields.testing_compliance.special_compliance' => ['nullable', 'array'],
            'datasheet_fields.testing_compliance.special_compliance.*' => ['required', Rule::enum(DatasheetCompliance::class)],
            'datasheet_fields.testing_compliance.other_compliance' => ['nullable', 'string', 'max:500'],
            'datasheet_fields.revision' => ['nullable', 'array:description,prepared_by,checked_by,approved_by'],
            'datasheet_fields.revision.description' => ['nullable', 'string', 'max:255'],
            'datasheet_fields.revision.prepared_by' => ['nullable', 'string', 'max:255'],
            'datasheet_fields.revision.checked_by' => ['nullable', 'string', 'max:255'],
            'datasheet_fields.revision.approved_by' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $draft = $this->input('draft_id');
                if (is_string($draft) && Str::isUuid($draft)
                    && ! is_array(session()->get('tenant_datasheet_drafts.'.$draft))) {
                    $validator->errors()->add('draft_id', 'This datasheet setup has expired. Start again.');

                    return;
                }

                if ($validator->errors()->has('valve_model_id')) {
                    return;
                }

                $model = ValveModel::find($this->input('valve_model_id'));
                $category = $this->input('snapshot_edits.model.valve_category', $model?->valve_category);
                if ($category !== 'Control / Modulating'
                    && filled($this->input('datasheet_fields.control_performance'))) {
                    $validator->errors()->add(
                        'datasheet_fields.control_performance',
                        'Control performance values are only valid for Control / Modulating models.',
                    );
                }
            },
        ];
    }
}
