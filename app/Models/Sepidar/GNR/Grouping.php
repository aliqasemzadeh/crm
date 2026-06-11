<?php

namespace App\Models\Sepidar\GNR;

use Illuminate\Database\Eloquent\Model;

class Grouping extends Model
{
    public $table = 'GNR.Grouping';
    public $connection = 'sqlsrv';
    public $primaryKey = 'GroupingID';
    public $fillable = ['GroupingID'];

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Grouping::class, 'ParentGroupRef', 'GroupingID');
    }

    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Grouping::class, 'ParentGroupRef', 'GroupingID');
    }

    public function getAllChildrenIds(): array
    {
        return \Illuminate\Support\Facades\Cache::remember(
            "grouping_{$this->GroupingID}_all_children_ids",
            now()->addHours(6),
            function () {
                $ids = [$this->GroupingID];

                foreach ($this->children as $child) {
                    $ids = array_merge($ids, $child->getAllChildrenIds());
                }

                return $ids;
            }
        );
    }
}
