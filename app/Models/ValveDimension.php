<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValveDimension extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'valve_model_id',
        'size_inch',
        'size_dn',
        'pressure_class',
        'end_connection',
        'face_to_face_mm',
        'end_to_end_mm',
        'flange_od_mm',
        'center_to_top_mm',
        'weight_kg',
        'extra_dimensions',
    ];

    protected function casts(): array
    {
        return [
            'extra_dimensions' => 'array',
            'face_to_face_mm' => 'decimal:2',
            'end_to_end_mm' => 'decimal:2',
            'flange_od_mm' => 'decimal:2',
            'center_to_top_mm' => 'decimal:2',
            'weight_kg' => 'decimal:2',
        ];
    }

    public function valveModel(): BelongsTo
    {
        return $this->belongsTo(ValveModel::class);
    }
}
