<?php

namespace App\Console\Commands;

use App\Http\Controllers\IncidentDeskController;
use App\Models\MaintenanceType;
use Illuminate\Console\Command;

class KillaSyncMaintenanceTypes extends Command
{
    protected $signature = 'killa:sync-maintenance-types';

    protected $description = 'Seed the Incident Desk category/task-type taxonomy into maintenance_types (idempotent, firstOrCreate by name)';

    public function handle(): int
    {
        $names = collect(IncidentDeskController::TASK_CATEGORIES)
            ->flatMap(fn (array $taskTypes, string $category) => array_merge([$category], $taskTypes))
            ->unique()
            ->values();

        $created = 0;
        $restored = 0;

        foreach ($names as $name) {
            $existing = MaintenanceType::withTrashed()->firstWhere('name', $name);

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                    $restored++;
                }

                continue;
            }

            MaintenanceType::create(['name' => $name]);
            $created++;
        }

        $this->info("Maintenance types synced: {$created} created, {$restored} restored, ".($names->count() - $created - $restored).' already present.');
        $this->info('Total maintenance types: '.MaintenanceType::count());

        return self::SUCCESS;
    }
}
