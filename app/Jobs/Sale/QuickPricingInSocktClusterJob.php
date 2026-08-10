<?php

namespace App\Jobs\Sale;

use App\Models\Sepidar\Local\INV\Cluster;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class QuickPricingInSocktClusterJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $scanned = 0;
        $updated = 0;
        $added = 0;
        $hidden = 0;

        Cluster::query()->lazy()->each(function (Cluster $cluster) use (&$scanned, &$updated, &$added, &$hidden): void {
            $scanned++;

            $previousAvailable = collect($cluster->available_item_refs ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();

            if (! $cluster->syncAvailableItemRefs()) {
                return;
            }

            $updated++;

            $cluster->refresh();

            $currentAvailable = collect($cluster->available_item_refs ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();

            $added += $currentAvailable->diff($previousAvailable)->count();
            $hidden += $previousAvailable->diff($currentAvailable)->count();
        });

        Log::info('QuickPricingInSocktClusterJob finished.', [
            'clusters_scanned' => $scanned,
            'clusters_updated' => $updated,
            'items_added_back' => $added,
            'items_hidden' => $hidden,
        ]);
    }
}
