<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\IncidentTicket;
use App\Models\User;
use App\Services\Killa\IncidentMaintenanceSync;
use Illuminate\Console\Command;

class KillaBackfillIncidentMaintenances extends Command
{
    protected $signature = 'killa:backfill-incident-maintenances {--dry-run : Report what would change without writing anything}';

    protected $description = "Tag incident tickets with the requester's checked-out device and create the paired maintenances rows (idempotent)";

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $tagged = 0;
        $untagged = 0;
        $synced = 0;
        $failed = 0;

        IncidentTicket::whereNull('maintenance_id')
            ->chunkById(200, function ($tickets) use ($dryRun, &$tagged, &$untagged, &$synced, &$failed) {
                foreach ($tickets as $ticket) {
                    if (! $ticket->asset_id) {
                        $asset = $ticket->requester_user_id
                            ? Asset::where('assigned_to', $ticket->requester_user_id)
                                ->where('assigned_type', User::class)
                                ->orderByDesc('last_checkout')
                                ->orderByDesc('updated_at')
                                ->first()
                            : null;

                        if ($asset) {
                            if (! $dryRun) {
                                $ticket->asset_id = $asset->id;
                                $ticket->saveQuietly();
                            }
                            $tagged++;
                        } else {
                            $untagged++;
                        }
                    }

                    if ($ticket->asset_id) {
                        if ($dryRun) {
                            $synced++;

                            continue;
                        }

                        if (IncidentMaintenanceSync::sync($ticket)) {
                            $synced++;
                        } else {
                            $failed++;
                        }
                    }
                }
            });

        $this->info(($dryRun ? '[dry-run] ' : '')."Device-tagged: {$tagged}, no device to tag: {$untagged}, maintenance rows written: {$synced}, failed: {$failed}.");

        if ($dryRun) {
            $this->warn('Dry run — nothing was written. Re-run without --dry-run to apply.');
        }

        return self::SUCCESS;
    }
}
