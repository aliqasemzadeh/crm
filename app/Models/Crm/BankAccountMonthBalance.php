<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class BankAccountMonthBalance extends Model
{
    protected $table = 'crm_bank_account_month_balances';

    protected $fillable = [
        'fiscal_year_ref',
        'jalali_year',
        'jalali_month',
        'bank_account_id',
        'account_label',
        'ending_balance',
        'min_balance',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year_ref' => 'integer',
            'jalali_year' => 'integer',
            'jalali_month' => 'integer',
            'bank_account_id' => 'integer',
            'ending_balance' => 'decimal:2',
            'min_balance' => 'decimal:2',
        ];
    }
}
