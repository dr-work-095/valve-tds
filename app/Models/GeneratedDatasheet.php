<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedDatasheet extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'created_by_user_id',
        'valve_model_id',
        'document_number',
        'revision',
        'parent_datasheet_id',
        'client_name',
        'project_reference',
        'selected_specs',
        'status',
        'pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'selected_specs' => 'array',
        ];
    }

    public function valveModel(): BelongsTo
    {
        return $this->belongsTo(ValveModel::class);
    }
}
