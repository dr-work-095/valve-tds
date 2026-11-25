<?php

namespace App\Http\Requests\Admin;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
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
            'status' => ['required', Rule::in(['trial', 'active', 'suspended'])],
            'subscription_ends_at' => ['nullable', 'date'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', Rule::unique('central.users', 'email')],
            'temporary_password' => ['required', 'string', 'min:12', 'max:255'],
            'active_users_limit' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'active_valve_models_limit' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'monthly_pdfs_limit' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ];
    }
}
