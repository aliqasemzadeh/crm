<?php

namespace App\Models\Voip;

use Illuminate\Database\Eloquent\Model;

class Phone extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'number',
        'name',
        'name_latin',
    ];
}
