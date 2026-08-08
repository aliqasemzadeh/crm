<?php

namespace App\Models\Crm\SetareganCo;

use Illuminate\Database\Eloquent\Model;

class CashBackRule extends Model
{
    protected $table = 'crm_cash_back_rules';

    protected $fillable = [
        'start_amount',
        'end_amount',
        'cash_back_amount',
        'is_percent',
        'activation_delay_days',
        'usage_duration_days',
    ];

    protected function casts(): array
    {
        return [
            'start_amount' => 'integer',
            'end_amount' => 'integer',
            'cash_back_amount' => 'integer',
            'is_percent' => 'boolean',
            'activation_delay_days' => 'integer',
            'usage_duration_days' => 'integer',
        ];
    }
}
