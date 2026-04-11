<?php

namespace App\Models\Workspace;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstructionFile extends Model
{
    use SoftDeletes;
    protected $table = 'workspace_instruction_files';

    public function instruction()
    {
        return $this->belongsTo(Instruction::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
