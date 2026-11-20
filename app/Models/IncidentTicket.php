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
     * Tries the name as written (full name / username / display name), then
     * a normalized variant: parenthetical nicknames, middle initials, and
     * honorific prefixes stripped ("Gao Qi (Chloe)" → "Gao Qi",
     * "Sukhvir M. Verma" → "Sukhvir Verma", "Ma. Teresita Garma" →
     * "Teresita Garma"). Deliberately conservative — no fuzzy matching, so
     * near-miss spellings stay unmatched rather than guessing wrong.
     */
    public static function resolveRequesterUserId(?string $name): ?int
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        $candidates = [$name];

        $stripped = trim(preg_replace('/\s*\([^)]*\)/', '', $name));
        $stripped = trim(preg_replace('/\b[A-Z]\.\s+/', '', $stripped));
        $stripped = trim(preg_replace('/^(Mr|Mrs|Ms|Miss|Dr|Ma)\.\s+/i', '', $stripped));

        if ($stripped !== '' && mb_strtolower($stripped) !== mb_strtolower($name)) {
            $candidates[] = $stripped;
        }

        foreach ($candidates as $candidate) {
            $lower = mb_strtolower($candidate);

            $user = User::whereRaw("LOWER(TRIM(CONCAT_WS(' ', first_name, last_name))) = ?", [$lower])
                ->orWhereRaw('LOWER(username) = ?', [$lower])
                ->orWhereRaw('LOWER(display_name) = ?', [$lower])
                ->first();

            if ($user) {
                return $user->id;
            }
        }

        return null;
    }
}
