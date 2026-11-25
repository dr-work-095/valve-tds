<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ValveModel extends Model
{
    protected $connection = 'tenant';

    protected $table = 'valve_models';

    protected $fillable = [
        'name',
        'category',
        'design_standard',
        'testing_standard',
        'face_to_face_standard',
        'end_connection_standard',
        'schematic_image_path',
        'custom_attributes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'custom_attributes' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function dimensions(): HasMany
    {
        return $this->hasMany(ValveDimension::class);
    }

    public function bomTemplates(): HasMany
    {
        return $this->hasMany(ValveBomTemplate::class);
    }

    public function isReady(): bool
    {
        return $this->is_active
            && $this->dimensions()->exists()
            && $this->bomTemplates()->exists();
    }
}
