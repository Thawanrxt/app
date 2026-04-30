<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardWorkItem extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'plot_id',
        'activity_event_id',
        'farmer_name',
        'plot_code',
        'task_title',
        'issue_category',
        'status',
        'priority',
        'progress_percent',
        'due_date',
        'last_activity_at',
        'responded_at',
        'response_required',
        'latest_note',
        'meta',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'last_activity_at' => 'datetime',
            'responded_at' => 'datetime',
            'resolved_at' => 'datetime',
            'response_required' => 'boolean',
            'meta' => 'array',
        ];
    }
}
