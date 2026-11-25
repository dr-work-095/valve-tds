<?php

namespace App\Http\Requests\Tenant;

use App\Models\ValveDimension;
use Illuminate\Foundation\Http\FormRequest;

class UpdateValveDimensionRequest extends FormRequest
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
            'size_inch' => ['required', 'string', 'max:20'],
            'size_dn' => ['nullable', 'string', 'max:20'],
            'pressure_class' => ['required', 'string', 'max:50'],
            'end_connection' => ['required', 'string', 'max:50'],
            'face_to_face_mm' => ['nullable', 'decimal:0,2', 'min:0'],
            'end_to_end_mm' => ['nullable', 'decimal:0,2', 'min:0'],
            'flange_od_mm' => ['nullable', 'decimal:0,2', 'min:0'],
            'center_to_top_mm' => ['nullable', 'decimal:0,2', 'min:0'],
            'weight_kg' => ['nullable', 'decimal:0,2', 'min:0'],
            'extra_dimensions' => ['nullable', 'array'],
            'extra_dimensions.*.key' => ['required_with:extra_dimensions', 'string', 'max:100'],
            'extra_dimensions.*.value' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            if ($validator->errors()->hasAny(['size_inch', 'pressure_class', 'end_connection'])) {
                return;
            }

            $exists = ValveDimension::where('valve_model_id', (int) $this->route('valveModel'))
                ->whereKeyNot((int) $this->route('dimension'))
                ->where('size_inch', $this->input('size_inch'))
                ->where('pressure_class', $this->input('pressure_class'))
                ->where('end_connection', $this->input('end_connection'))
                ->exists();

            if ($exists) {
                $validator->errors()->add('size_inch', 'This dimension combination already exists for the valve model.');
            }
        }];
    }
}
