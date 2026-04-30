<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HarvestTrackingActivity extends Model
{
    protected $fillable = [
        'farmer_name',
        'plot_code',
        'round_number',
        'activity_name',
        'activity_date',
        'started_at',
        'ended_at',
        'yield_amount_kg',
        'moisture_percent',
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
            'started_at' => 'date',
            'ended_at' => 'date',
            'reviewed_at' => 'datetime',
            'round_number' => 'integer',
            'yield_amount_kg' => 'float',
            'moisture_percent' => 'float',
        ];
    }
}
