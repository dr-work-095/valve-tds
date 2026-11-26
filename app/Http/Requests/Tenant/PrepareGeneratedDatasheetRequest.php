<?php

namespace App\Http\Requests\Tenant;

use App\Models\ValveDimension;
use App\Support\ValveModelReadiness;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PrepareGeneratedDatasheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_name' => ['required', 'string', 'max:255'],
            'project_reference' => ['nullable', 'string', 'max:255'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'valve_model_id' => ['required', 'integer', 'exists:tenant.valve_models,id'],
            'dimension_id' => ['required', 'integer', 'exists:tenant.valve_dimensions,id'],
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['valve_model_id', 'dimension_id'])) {
                    return;
                }

                $model = (new ValveModelReadiness)
                    ->selectableQuery()
                    ->find($this->integer('valve_model_id'));

                if (! $model) {
                    $validator->errors()->add('valve_model_id', 'Select a ready valve model.');

                    return;
                }

                $dimensionBelongsToModel = ValveDimension::query()
                    ->whereKey($this->integer('dimension_id'))
                    ->where('valve_model_id', $model->id)
                    ->exists();

                if (! $dimensionBelongsToModel) {
                    $validator->errors()->add(
                        'dimension_id',
                        'The selected dimension does not belong to the valve model.',
                    );
                }
            },
        ];
    }
}
