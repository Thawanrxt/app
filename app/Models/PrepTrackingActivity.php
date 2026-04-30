<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrepTrackingActivity extends Model
{
    protected $fillable = [
        'farmer_name',
        'plot_code',
        'round_number',
        'activity_name',
        'method',
        'activity_date',
        'soil_preparation_method',
        'tillage_depth',
        'soil_result',
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
