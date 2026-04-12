<?php

namespace App\Models\Workspace;

use Illuminate\Database\Eloquent\Model;

class RequestType extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    public $table = 'workspace_request_types';

    protected $fillable = [
        'name',
        'title',
        'description',
        'schema',
        'is_active',
    ];

    protected $casts = [
        'schema' => 'array',
        'is_active' => 'boolean',
    ];
}
