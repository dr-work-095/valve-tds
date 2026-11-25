<?php

namespace App\Http\Requests\Admin;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'plan' => ['required', Rule::in(Tenant::PLANS)],
            'status' => ['required', Rule::in(Tenant::STATUSES)],
            'subscription_ends_at' => ['nullable', 'date'],
            'payment_note' => ['nullable', 'string', 'max:2000'],
            'active_users_limit' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'active_valve_models_limit' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'monthly_pdfs_limit' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ];
    }
}
