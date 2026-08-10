<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemActionLog extends Model
{
    protected $fillable = [
        'command',
        'parameters',
        'output',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parameters' => 'array',
        ];
    }
}
