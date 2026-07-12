<?php

namespace App\Models\Sepidar\FMK;

use App\Models\Sepidar\SLS\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    public $table = 'FMK.User';

    public $connection = 'sqlsrv';

    protected $primaryKey = 'UserID';

    public $timestamps = false;

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'Creator', 'UserID');
    }

    public function modifiedInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'LastModifier', 'UserID');
    }
}
