<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallReportParty extends Model
{
    protected $table = 'crm_call_report_parties';

    protected $fillable = [
        'call_report_id',
        'rank',
        'phone',
        'call_count',
        'party_id',
        'party_name',
        'has_invoice_48h',
        'invoice_summary',
        'identity_note',
        'transcripts',
        'analysis',
        'outcome',
    ];

    protected function casts(): array
    {
        return [
            'rank' => 'integer',
            'call_count' => 'integer',
            'party_id' => 'integer',
            'has_invoice_48h' => 'boolean',
            'transcripts' => 'array',
            'analysis' => 'array',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(CallReport::class, 'call_report_id');
    }
}
