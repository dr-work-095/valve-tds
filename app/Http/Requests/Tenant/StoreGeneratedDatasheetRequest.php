<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

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
        ];
    }
}
