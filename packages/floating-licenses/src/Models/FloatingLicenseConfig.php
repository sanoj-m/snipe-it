<?php

namespace SnipeIt\FloatingLicenses\Models;

use App\Models\Company;
use App\Models\License;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FloatingLicenseConfig extends Model
{
    use SoftDeletes;

    public const COST_MODE_POOL_SLOT = 'pool_slot';

    public const COST_MODE_ACTIVE_USER = 'active_user';

    protected $table = 'floating_license_configs';

    protected $fillable = [
        'license_id',
        'pool_size',
        'total_cost',
        'cost_mode',
        'allow_over_allocation',
        'lease_duration_minutes',
        'idle_timeout_minutes',
    ];

    protected $casts = [
        'license_id' => 'integer',
        'pool_size' => 'integer',
        'total_cost' => 'decimal:2',
        'allow_over_allocation' => 'boolean',
        'lease_duration_minutes' => 'integer',
        'idle_timeout_minutes' => 'integer',
    ];

    /**
     * Scope pool configs to the current user's companies under Full Multiple
     * Company Support, via the underlying license (configs carry no
     * company_id of their own). No-op when FMCS is off, for superusers, or
     * on the CLI — the same rules Company::scopeCompanyables() applies.
     */
    public function scopeCompanyScoped(Builder $query): Builder
    {
        if (! Company::isFullMultipleCompanySupportEnabled()) {
            return $query;
        }

        return $query->whereHas('license', fn (Builder $licenseQuery) => Company::scopeCompanyables($licenseQuery));
    }

    /**
     * The underlying Snipe-IT license this pool is attached to.
     */
    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class, 'license_id');
    }

    /**
     * All allocations (active and historical) drawn from this pool.
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(FloatingLicenseAllocation::class, 'license_id', 'license_id');
    }

    /**
     * Only the currently active allocations for this pool.
     */
    public function activeAllocations(): HasMany
    {
        return $this->allocations()->active();
    }
}
