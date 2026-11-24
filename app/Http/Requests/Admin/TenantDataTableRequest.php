<?php

namespace App\Http\Requests\Admin;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TenantDataTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'draw' => ['sometimes', 'integer', 'min:0'],
            'start' => ['sometimes', 'integer', 'min:0'],
            'length' => ['sometimes', 'integer', Rule::in([10, 25, 50, 100])],
            'search' => ['sometimes', 'array'],
            'search.value' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(Tenant::STATUSES)],
            'plan' => ['nullable', Rule::in(Tenant::PLANS)],
            'order' => ['sometimes', 'array'],
            'order.0.column' => ['nullable', 'integer', Rule::in([0, 1, 2, 3])],
            'order.0.dir' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }
}
