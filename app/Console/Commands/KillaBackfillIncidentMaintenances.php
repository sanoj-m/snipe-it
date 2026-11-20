<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\IncidentTicket;
use App\Models\User;
use App\Services\Killa\IncidentMaintenanceSync;
use Illuminate\Console\Command;

class KillaBackfillIncidentMaintenances extends Command
{
    protected $signature = 'killa:backfill-incident-maintenances
                            {--dry-run : Report what would change without writing anything}
                            {--category= : Only process tickets in this category (e.g. Software)}
                            {--only-untagged : Only process tickets with no device linked}';

    protected $description = "Tag incident tickets with the requester's checked-out device and create the paired maintenances rows (idempotent)";

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $category = trim((string) $this->option('category'));
        $onlyUntagged = (bool) $this->option('only-untagged');

        $tagged = 0;
        $noUser = 0;
        $noDevice = 0;
        $synced = 0;
        $failed = 0;
        $scanned = 0;

        $query = IncidentTicket::whereNull('maintenance_id');

        if ($onlyUntagged) {
            $query->whereNull('asset_id');
        }

        if ($category !== '') {
            $query->whereRaw('LOWER(category) = ?', [mb_strtolower($category)]);
        }

        $query->chunkById(200, function ($tickets) use ($dryRun, &$tagged, &$noUser, &$noDevice, &$synced, &$failed, &$scanned) {
            foreach ($tickets as $ticket) {
                $scanned++;

                // Re-resolve the requester when the name is known but the
                // link was never made (older imports).
                if (! $ticket->requester_user_id && $ticket->requester_name) {
                    $ticket->requester_user_id = IncidentTicket::resolveRequesterUserId($ticket->requester_name);

                    if ($ticket->requester_user_id && ! $dryRun) {
                        $ticket->saveQuietly();
                    }
                }

                if (! $ticket->asset_id) {
                    if (! $ticket->requester_user_id) {
                        $noUser++;

                        continue;
                    }

                    $asset = Asset::where('assigned_to', $ticket->requester_user_id)
                        ->where('assigned_type', User::class)
                        ->orderByDesc('last_checkout')
                        ->orderByDesc('updated_at')
                        ->first();

                    if (! $asset) {
                        $noDevice++;

                        continue;
                    }

                    if (! $dryRun) {
                        $ticket->asset_id = $asset->id;
                        $ticket->saveQuietly();
                    }
                    $tagged++;
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

        $this->info(($dryRun ? '[dry-run] ' : '')."Scanned: {$scanned}, device-tagged: {$tagged}, no requester match: {$noUser}, no device checked out: {$noDevice}, maintenance rows written: {$synced}, failed: {$failed}.");

        if ($dryRun) {
            $this->warn('Dry run — nothing was written. Re-run without --dry-run to apply.');
        }

        return self::SUCCESS;
    }
}
