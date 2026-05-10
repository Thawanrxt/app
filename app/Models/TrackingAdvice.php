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

    public function getResolvedMessageAttribute(): ?string
    {
        return $this->message
            ?? $this->getAttribute('advice_message')
            ?? null;
    }

    public function getResolvedSentAtAttribute(): mixed
    {
        $sentAt = $this->getAttribute('sent_at');
        if ($sentAt) {
            return $sentAt;
        }

        $adviceStatus = $this->getAttribute('advice_status');
        if ($adviceStatus === 'sent') {
            return $this->getAttribute('updated_at') ?? $this->getAttribute('created_at');
        }

        return null;
    }
}
