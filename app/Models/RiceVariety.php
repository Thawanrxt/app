<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiceVariety extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'is_active',
        'rice_type',
        'name',
        'standard_duration_days',
        'disease_resistance',
        'pest_resistances',
        'grow_duration_days',
        'recommended_season',
        'image_url',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'pest_resistances' => 'array',
        ];
    }
}
