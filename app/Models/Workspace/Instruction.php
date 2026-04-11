<?php

namespace App\Models\Workspace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Instruction extends Model
{
    use SoftDeletes;
    protected $table = 'workspace_instructions';

    protected $fillable = ['title', 'body', 'status', 'user_id'];

    public function files()
    {
        return $this->hasMany(InstructionFile::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
