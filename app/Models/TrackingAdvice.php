<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackingAdvice extends Model
{
    protected $table = 'tracking_advices';

    protected $fillable = [
        'page_key',
        'page_title',
        'message',
        'attachment_path',
        'attachment_name',
        'sent_at',
        'sent_by',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }
}
