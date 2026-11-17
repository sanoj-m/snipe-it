<?php

namespace App\Console\Commands;

use App\Http\Controllers\IncidentDeskController;
use App\Models\IncidentTicket;
use Carbon\Carbon;
use Illuminate\Console\Command;

class KillaImportIncidentTickets extends Command
{
    protected $signature = 'killa:import-incident-tickets {file : Path to the JSON export from the standalone incident desk tool}';

    protected $description = 'Import incident tickets from the standalone Killa incident-desk JSON export (idempotent, keyed on the original ticket id)';

    public function handle(): int
    {
        $file = $this->argument('file');

        if (! is_readable($file)) {
            $this->error("File not readable: {$file}");

            return self::FAILURE;
        }

        $tickets = json_decode(file_get_contents($file), true);

        if (! is_array($tickets)) {
            $this->error('File does not contain a JSON array.');

            return self::FAILURE;
        }

        $created = 0;
        $skipped = 0;

        foreach ($tickets as $row) {
            if (! isset($row['id'], $row['details'], $row['createdAt'])) {
                $skipped++;

                continue;
            }

            if (IncidentTicket::withTrashed()->where('legacy_id', $row['id'])->exists()) {
                $skipped++;

                continue;
            }

            $requester = isset($row['user']) ? trim((string) $row['user']) : null;
            $issue = isset($row['issue']) ? trim((string) $row['issue']) : null;

            $ticket = new IncidentTicket;
            $ticket->legacy_id = (int) $row['id'];
            $ticket->requester_name = $requester !== '' ? $requester : null;
            $ticket->requester_user_id = IncidentTicket::resolveRequesterUserId($requester);
            $ticket->category = $row['category'] ?? null;
            $ticket->task_type = $row['taskType'] ?? null;
            $ticket->issue = $issue !== '' ? $issue : null;
            $ticket->details = trim((string) $row['details']) !== ''
                ? trim((string) $row['details'])
                : IncidentDeskController::combineDetails($ticket->requester_name, $ticket->task_type, $ticket->issue);
            $ticket->status = in_array($row['status'] ?? '', IncidentTicket::STATUSES, true) ? $row['status'] : 'Open';
            $ticket->created_by = null;
            $ticket->created_at = Carbon::parse($row['createdAt']);
            $ticket->updated_at = $ticket->created_at;
            $ticket->save();

            $created++;
        }

        $this->info("Imported {$created} ticket(s), skipped {$skipped} (already present or invalid).");
        $this->info('Total tickets in table: '.IncidentTicket::count());

        return self::SUCCESS;
    }
}
