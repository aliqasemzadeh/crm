<?php

namespace App\Models\Sepidar\ACC;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $table = 'ACC.Account';
    protected $connection = 'sqlsrv';
    protected $primaryKey = 'AccountId';

    public function parent()
    {
        return $this->belongsTo(Account::class, 'ParentAccountRef', 'AccountId');
    }

    public function children()
    {
        return $this->hasMany(Account::class, 'ParentAccountRef', 'AccountId');
    }

    public function topics()
    {
        return $this->belongsToMany(Topic::class, 'ACC.AccountTopic', 'AccountSLRef', 'TopicRef', 'AccountId', 'TopicId');
    }
}
