<?php

namespace App\Models\SetareganCo;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $connection = 'setaregan_sqlsrv';
    protected $table = 'City';
    protected $primaryKey = 'Id';
    public $timestamps = false;

    public function state()
    {
        return $this->belongsTo(State::class, 'StateId', 'Id');
    }
}
