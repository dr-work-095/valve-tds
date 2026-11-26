<?php

namespace App\Http\Requests\Tenant;

use App\Enums\DatasheetAccessory;
use App\Enums\DatasheetCompliance;
use App\Models\ValveModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreGeneratedDatasheetRequest extends FormRequest
{
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
            'bom_materials' => ['required', 'array', 'min:1'],
            'bom_materials.*.bom_template_id' => ['required', 'integer', 'exists:tenant.valve_bom_templates,id'],
            'bom_materials.*.material_id' => ['required', 'integer', 'exists:tenant.materials,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
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
            'datasheet_fields.mechanical' => ['nullable', 'array:line_size'],
            'datasheet_fields.mechanical.line_size' => ['nullable', 'string', 'max:100'],
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
                if ($validator->errors()->has('valve_model_id')) {
                    return;
                }

                $model = ValveModel::find($this->input('valve_model_id'));
                if ($model?->valve_category !== 'Control / Modulating'
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
