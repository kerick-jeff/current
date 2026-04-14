<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'title',
        'filename',
        'content',
        'embedding',
    ];

    protected function casts(): array
    {
        return [
            // Cast the vector column to array for Eloquent to
            // serialize/deserialize it correctly with pgvector
            'embedding' => 'array',
        ];
    }
}
