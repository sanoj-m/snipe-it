<?php

namespace SnipeIt\FloatingLicenses\Console;

use App\Models\LicenseSeat;
use App\Models\User;
use Illuminate\Console\Command;
use SnipeIt\FloatingLicenses\Models\FloatingLicenseAllocation;
use SnipeIt\FloatingLicenses\Models\FloatingLicenseConfig;
use SnipeIt\FloatingLicenses\Support\BulkUserAssignment;

class ConvertFloatingLicensesToStandard extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'floating-licenses:convert-to-standard
                            {--except=rhino : Skip licenses whose name contains this string (case-insensitive)}
                            {--dry-run : Show what would change without writing anything}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert floating licenses to standard seat-based licenses, keeping all assigned users as seat checkouts';

    /**
     * Execute the console command.
     */
    public function handle(BulkUserAssignment $bulk): int
    {
        $except = strtolower((string) $this->option('except'));
        $dryRun = (bool) $this->option('dry-run');

        $configs = FloatingLicenseConfig::with('license')->get();

        $rows = [];

        foreach ($configs as $config) {
            $license = $config->license;

            if (! $license) {
                $rows[] = ['(missing license #'.$config->license_id.')', '-', '-', '-', 'skipped, license not found'];

                continue;
            }

            if ($except !== '' && str_contains(strtolower((string) $license->name), $except)) {
                $rows[] = [$license->name, '-', '-', '-', 'skipped, matches --except='.$except];

                continue;
            }

            $allocations = FloatingLicenseAllocation::where('license_id', $license->id)
                ->active()
                ->get();

            $userIds = $allocations->pluck('user_id')->unique()->values();
            $userCount = $userIds->count();
            $oldSeats = (int) $license->seats;
            $newSeats = max($oldSeats, $userCount);

            if ($dryRun) {
                $rows[] = [
                    $license->name,
                    $userCount,
                    $oldSeats,
                    $newSeats,
                    'dry-run: would convert '.$userCount.' user(s) to seats and disable floating',
                ];

                continue;
            }

            $converted = 0;
            $skippedExisting = 0;
            $failed = 0;

            if ($newSeats !== $oldSeats) {
                $license->seats = $newSeats;
                $license->save();
            }

            foreach ($userIds as $userId) {
                $user = User::find($userId);

                if (! $user) {
                    $failed++;

                    continue;
                }

                $seated = LicenseSeat::where('license_id', $license->id)
                    ->where('assigned_to', $user->id)
                    ->whereNull('deleted_at')
                    ->exists();

                if ($seated) {
                    $skippedExisting++;
                } elseif ($bulk->assignSeatToUser($license, $user, allowUnreassignable: true)) {
                    $seated = true;
                    $converted++;
                } else {
                    $failed++;
                }

                // Release the floating allocations only when the user ended
                // up seated (or already was) so no double-assignment or
                // stranded user is left behind.
                if ($seated) {
                    FloatingLicenseAllocation::where('license_id', $license->id)
                        ->where('user_id', $user->id)
                        ->active()
                        ->update([
                            'status' => FloatingLicenseAllocation::STATUS_RELEASED,
                            'released_at' => now(),
                        ]);
                }
            }

            // Only disable floating once no active allocations remain, so a
            // partially-failed conversion never strands a user.
            $remaining = FloatingLicenseAllocation::where('license_id', $license->id)->active()->count();

            if ($remaining === 0) {
                $config->delete();
            }

            $rows[] = [
                $license->name,
                $userCount,
                $oldSeats,
                $newSeats,
                "converted={$converted}, already-seated={$skippedExisting}, failed={$failed}"
                    .($remaining === 0 ? ', floating disabled' : ", floating KEPT ({$remaining} active allocation(s) left)"),
            ];
        }

        $this->table(['License', 'Floating users', 'Seats (old)', 'Seats (new)', 'Result'], $rows);

        if ($dryRun) {
            $this->info('Dry run only. Re-run without --dry-run to apply.');
        }

        return Command::SUCCESS;
    }
}
