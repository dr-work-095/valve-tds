<?php

namespace App\Http\Requests\Tenant;

use App\Models\Material;
use App\Models\ValveBomTemplate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateValveBomTemplateRequest extends FormRequest
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
            'part_number' => ['required', 'integer', 'min:1'],
            'part_name' => ['required', 'string', 'max:150'],
            'standard_material_id' => ['required', 'integer', 'exists:tenant.materials,id'],
            'optional_material_ids' => ['nullable', 'array', 'max:50'],
            'optional_material_ids.*' => ['integer'],
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            $modelId = (int) $this->route('valveModel');
            $bomId = (int) $this->route('bom');
            if ($this->filled('part_number') && ValveBomTemplate::where('valve_model_id', $modelId)->whereKeyNot($bomId)->where('part_number', $this->integer('part_number'))->exists()) {
                $validator->errors()->add('part_number', 'This part number already exists for the valve model.');
            }

            $materialIds = array_filter(array_merge(
                [$this->integer('standard_material_id')],
                array_map('intval', (array) $this->input('optional_material_ids', [])),
            ));
            if ($materialIds !== []) {
                $validIds = Material::whereIn('id', $materialIds)->where('is_active', true)->pluck('id')->map(fn ($id) => (int) $id)->all();
                if (array_diff($materialIds, $validIds) !== []) {
                    $validator->errors()->add('standard_material_id', 'Selected materials must be active tenant materials.');
                }
            }
        }];
    }
}
