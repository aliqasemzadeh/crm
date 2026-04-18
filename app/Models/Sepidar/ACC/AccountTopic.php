<?php

namespace App\Models\Sepidar\ACC;

use Illuminate\Database\Eloquent\Model;

class AccountTopic extends Model
{
    protected $table = 'ACC.AccountTopic';
    protected $connection = 'sqlsrv';
    protected $primaryKey = 'AccountTopicId';
}
