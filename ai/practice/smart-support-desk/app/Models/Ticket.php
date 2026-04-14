<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject',
        'body',
        'status',
        'ai_category',
        'ai_urgency',
        'ai_suggested_reply',
        'ai_auto_resolvable',
        'ai_analysis',
    ];

    protected function casts(): array
    {
        return [
            'ai_auto_resolvable' => 'boolean',
            'ai_analysis'        => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }
}
