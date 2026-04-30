<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PestTrackingActivity extends Model
{
    protected $fillable = [
        'farmer_name',
        'plot_code',
        'round_number',
        'activity_name',
        'pest_type',
        'chemical_name',
        'mix_ratio',
        'activity_date',
        'details',
        'issue_found',
        'image_url',
        'status',
        'reviewed_by',
        'reviewed_at',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
            'reviewed_at' => 'datetime',
            'round_number' => 'integer',
        ];
    }
}
