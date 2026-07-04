<?php

namespace App\Models\SetareganCo;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    protected $connection = 'setaregan_sqlsrv';
    protected $table = 'State';
    protected $primaryKey = 'Id';
    public $timestamps = false;

    public function cities()
    {
        return $this->hasMany(City::class, 'StateId', 'Id');
    }
}
