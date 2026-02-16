<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'content',
        'icon',
        'color',
        'link',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
        'is_active' => 'boolean',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('viewed_at')
            ->withTimestamps();
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true)
            ->where(fn($qq) =>
            $qq->whereNull('starts_at')
                ->orWhere('starts_at', '<=', now())
            )
            ->where(fn($qq) =>
            $qq->whereNull('ends_at')
                ->orWhere('ends_at', '>=', now())
            );
    }
}
