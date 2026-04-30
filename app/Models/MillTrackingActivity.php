<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MillTrackingActivity extends Model
{
    protected $fillable = [
        'farmer_name',
        'plot_code',
        'round_number',
        'activity_name',
        'activity_date',
        'mill_name',
        'queue_number',
        'document_number',
        'product_name',
        'vehicle_plate',
        'time_in',
        'time_out',
        'pre_mill_weight_kg',
        'post_mill_weight_kg',
        'net_weight_kg',
        'price_per_kg',
        'total_income',
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
            'pre_mill_weight_kg' => 'float',
            'post_mill_weight_kg' => 'float',
            'net_weight_kg' => 'float',
            'price_per_kg' => 'float',
            'total_income' => 'float',
        ];
    }
}
