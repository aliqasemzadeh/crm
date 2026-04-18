<?php

namespace App\Models\Sepidar\ACC;

use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    protected $table = 'ACC.Topic';
    protected $connection = 'sqlsrv';
    protected $primaryKey = 'TopicId';

    public function accounts()
    {
        return $this->belongsToMany(Account::class, 'ACC.AccountTopic', 'TopicRef', 'AccountSLRef');
    }
}
