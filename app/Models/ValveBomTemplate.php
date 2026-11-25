<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValveBomTemplate extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'valve_model_id',
        'part_number',
        'part_name',
        'standard_material_id',
        'optional_material_ids',
    ];

    protected function casts(): array
    {
        return [
            'optional_material_ids' => 'array',
        ];
    }

    public function valveModel(): BelongsTo
    {
        return $this->belongsTo(ValveModel::class);
    }

    public function standardMaterial(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'standard_material_id');
    }
}
