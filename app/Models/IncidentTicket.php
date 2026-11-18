<?php

namespace App\Models;

use App\Services\Killa\IncidentMaintenanceSync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncidentTicket extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::saved(fn (IncidentTicket $ticket) => IncidentMaintenanceSync::sync($ticket));
        static::deleted(fn (IncidentTicket $ticket) => IncidentMaintenanceSync::deleteFor($ticket));
        static::restored(fn (IncidentTicket $ticket) => IncidentMaintenanceSync::sync($ticket));
    }

    public const STATUSES = ['Open', 'Closed', 'Need Vendor'];

    protected $fillable = [
        'legacy_id',
        'requester_name',
        'requester_user_id',
        'asset_id',
        'maintenance_id',
        'category',
        'task_type',
        'issue',
        'details',
        'status',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(Maintenance::class, 'maintenance_id');
    }

    /**
     * Resolve a free-typed requester name to an app user, if one matches.
     */
    public static function resolveRequesterUserId(?string $name): ?int
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        $user = User::whereRaw("LOWER(TRIM(CONCAT_WS(' ', first_name, last_name))) = ?", [mb_strtolower($name)])
            ->orWhereRaw('LOWER(username) = ?', [mb_strtolower($name)])
            ->first();

        return $user?->id;
    }
}
