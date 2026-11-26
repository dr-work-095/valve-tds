<?php

namespace App\Http\Requests\Tenant;

use App\Enums\ValveCategory;
use App\Enums\ValveType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreValveModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('tenant.valve_models', 'name')->where(fn ($query) => $query->where('valve_type', $this->input('valve_type')))],
            'valve_category' => ['required', Rule::enum(ValveCategory::class)],
            'valve_type' => ['required', Rule::enum(ValveType::class)],
            'design_standard' => ['required', 'string', 'max:255'],
            'testing_standard' => ['required', 'string', 'max:255'],
            'face_to_face_standard' => ['nullable', 'string', 'max:255'],
            'end_connection_standard' => ['nullable', 'string', 'max:255'],
            'custom_attributes' => ['nullable', 'array', 'max:50'],
            'custom_attributes.*.key' => ['required_with:custom_attributes', 'string', 'max:100'],
            'custom_attributes.*.value' => ['required_with:custom_attributes', 'string', 'max:500'],
            'is_active' => ['required', 'boolean'],
            'schematic' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }
}
