<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CallReport extends Model
{
    protected $table = 'crm_call_reports';

    protected $fillable = [
        'report_date',
        'status',
        'top_count',
        'summary',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'top_count' => 'integer',
        ];
    }

    public function parties(): HasMany
    {
        return $this->hasMany(CallReportParty::class, 'call_report_id')->orderBy('rank');
    }

    public function isProcessing(): bool
    {
        return in_array($this->status, ['pending', 'processing'], true);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
