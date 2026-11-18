<?php

namespace App\Services\Killa;

use App\Models\IncidentTicket;
use App\Models\Maintenance;
use App\Models\MaintenanceType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Keeps a real `maintenances` row in lockstep with an incident ticket that
 * has a device attached, so incidents surface on /maintenances and on the
 * asset's Maintenance tab. Killa-owned; called from the IncidentTicket
 * model hooks and the backfill command — MaintenancesController is not
 * patched.
 */
class IncidentMaintenanceSync
{
    /**
     * Create or update the maintenance row paired with the ticket.
     * No device on the ticket → any previously linked row is removed.
     */
    public static function sync(IncidentTicket $ticket): ?Maintenance
    {
        $maintenance = $ticket->maintenance_id
            ? Maintenance::withTrashed()->find($ticket->maintenance_id)
            : null;

        if (! $ticket->asset_id) {
            if ($maintenance) {
                $maintenance->delete();
                self::linkQuietly($ticket, null);
            }

            return null;
        }

        if ($maintenance?->trashed()) {
            $maintenance->restore();
        }

        $maintenance = $maintenance ?: new Maintenance;
        $maintenance->asset_id = $ticket->asset_id;
        $maintenance->maintenance_type_id = self::resolveMaintenanceTypeId($ticket);
        $maintenance->name = Str::limit($ticket->task_type ?: ($ticket->category ?: $ticket->details), 97, '...');
        $maintenance->start_date = $ticket->created_at;
        $maintenance->notes = $ticket->details;
        $maintenance->is_warranty = false;
        $maintenance->supplier_id = null;
        $maintenance->cost = null;

        if (! $maintenance->exists) {
            $maintenance->created_by = $ticket->created_by ?: auth()->id();
        }

        // A Closed ticket is a completed maintenance, stamped at the time
        // the ticket was filed (matches the "log it when you close it"
        // workflow the desk is built around).
        if ($ticket->status === 'Closed') {
            $maintenance->completed_at = $maintenance->completed_at ?: $ticket->created_at;
            $maintenance->completed_by = $maintenance->completed_by ?: ($ticket->created_by ?: auth()->id());
        } else {
            $maintenance->completed_at = null;
            $maintenance->completed_by = null;
        }

        try {
            if (! $maintenance->save()) {
                Log::error('Incident desk: maintenance validation failed', [
                    'ticket_id' => $ticket->id,
                    'errors' => $maintenance->getErrors()->toArray(),
                ]);

                return null;
            }
        } catch (\Throwable $e) {
            Log::error('Incident desk: maintenance sync failed', [
                'ticket_id' => $ticket->id,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }

        if ((int) $ticket->maintenance_id !== (int) $maintenance->id) {
            self::linkQuietly($ticket, $maintenance->id);
        }

        return $maintenance;
    }

    /**
     * Remove the paired maintenance row when the ticket is deleted.
     */
    public static function deleteFor(IncidentTicket $ticket): void
    {
        if (! $ticket->maintenance_id) {
            return;
        }

        Maintenance::withTrashed()->find($ticket->maintenance_id)?->delete();
        self::linkQuietly($ticket, null);
    }

    /**
     * task type → category → catch-all, firstOrCreate so the desk keeps
     * working even if a type was never seeded.
     */
    public static function resolveMaintenanceTypeId(IncidentTicket $ticket): int
    {
        $names = array_filter([
            $ticket->task_type,
            $ticket->category,
            'Other IT Support Request',
        ]);

        foreach ($names as $name) {
            $type = MaintenanceType::withTrashed()->firstWhere('name', $name);

            if ($type) {
                if ($type->trashed()) {
                    $type->restore();
                }

                return $type->id;
            }
        }

        return MaintenanceType::create(['name' => 'Other IT Support Request'])->id;
    }

    /**
     * Write the link without re-firing the ticket's saved hook.
     */
    private static function linkQuietly(IncidentTicket $ticket, ?int $maintenanceId): void
    {
        $ticket->maintenance_id = $maintenanceId;
        $ticket->saveQuietly();
    }
}
