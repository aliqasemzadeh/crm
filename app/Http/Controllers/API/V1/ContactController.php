<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Sepidar\GNR\PartyPhone;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function search(Request $request, $number = "")
    {
        return "سلام";
        $phone = PartyPhone::query()
                    ->with('party')
                    ->where('Phone', $number)
                    ->firstOrFail();

        $party = $phone->party;

        return trim(($party->Name ?? "") . " " . ($party->LastName ?? ""));
    }
}
