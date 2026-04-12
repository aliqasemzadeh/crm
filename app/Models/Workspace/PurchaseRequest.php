<?php

namespace App\Models\Workspace;

use Illuminate\Database\Eloquent\Model;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\Models\ProcessApproval;

class PurchaseRequest extends Model implements ApprovableModel
{
    use \RingleSoft\LaravelProcessApproval\Traits\Approvable;

    public function onApprovalCompleted(ProcessApproval $approval): bool
    {
        return true;
    }
}
