<?php

namespace App\Support;

use App\Models\ValveModel;

class ValveModelReadiness
{
    public function missingRequirements(ValveModel $model): array
    {
        $missing = [];

        if (! $model->is_active) {
            $missing[] = 'The valve model is inactive.';
        }
        if (! $model->dimensions()->exists()) {
            $missing[] = 'At least one dimension row is required.';
        }
        if (! $model->bomTemplates()->exists()) {
            $missing[] = 'At least one BOM row is required.';
        }

        return $missing;
    }

    public function assertReady(ValveModel $model): void
    {
        $missing = $this->missingRequirements($model);

        if ($missing !== []) {
            abort(422, 'Valve model is not ready: '.implode(' ', $missing));
        }
    }

    public function isReady(ValveModel $model): bool
    {
        return $this->missingRequirements($model) === [];
    }

    public function selectableQuery()
    {
        return ValveModel::query()
            ->where('is_active', true)
            ->whereHas('dimensions')
            ->whereHas('bomTemplates');
    }
}
