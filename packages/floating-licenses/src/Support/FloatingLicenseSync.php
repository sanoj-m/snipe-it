<?php

namespace SnipeIt\FloatingLicenses\Support;

use App\Models\License;
use App\Models\Setting;
use Illuminate\Http\Request;
use SnipeIt\FloatingLicenses\Models\FloatingLicenseConfig;
use SnipeIt\FloatingLicenses\Services\FloatingLicenseService;

class FloatingLicenseSync
{
    /**
     * Whether the addon's master switch (Admin > Settings > General) is on.
     */
    public static function isEnabled(): bool
    {
        return (Setting::getSettings()?->floating_licenses_enabled ?? '0') == '1';
    }

    /**
     * Resolve the floating pool config for a license.
     *
     * Returns the persisted config when one exists, null otherwise. Floating
     * is strictly opt-in per license: a config is only created when an admin
     * checks the floating checkbox on the license edit form
     * (syncFromRequest()). The master switch gates the feature globally via
     * isEnabled() but never turns individual licenses floating on its own.
     */
    public static function configForLicense(License $license): ?FloatingLicenseConfig
    {
        return FloatingLicenseConfig::where('license_id', $license->id)->first();
    }

    /**
     * Sync a license's floating config from the license create/edit form.
     *
     * The license's own attributes are the source of truth: seats = pool size,
     * purchase_cost = per-unit (per seat) price, so the pool's total cost is
     * purchase_cost × seats. Triggered by the License saved listener in
     * FloatingLicensesServiceProvider (gated to the licenses.store /
     * licenses.update form routes); a no-op when the master switch is off.
     */
    public static function syncFromRequest(License $license, Request $request): void
    {
        if (! self::isEnabled()) {
            return;
        }

        if ($request->boolean('floating_enabled')) {
            /** @var FloatingLicenseConfig $config */
            $config = FloatingLicenseConfig::withTrashed()->firstOrNew(['license_id' => $license->id]);

            $config->pool_size = (int) $license->seats;
            // purchase_cost is the per-unit (per seat) price; the pool's total
            // cost is unit price multiplied by the number of seats.
            $config->total_cost = is_numeric($license->purchase_cost)
                ? round(((float) $license->purchase_cost) * $config->pool_size, 2)
                : null;
            $config->cost_mode = in_array($request->input('floating_cost_mode'), [
                FloatingLicenseConfig::COST_MODE_POOL_SLOT,
                FloatingLicenseConfig::COST_MODE_ACTIVE_USER,
            ], true)
                ? $request->input('floating_cost_mode')
                : ($config->cost_mode ?: FloatingLicenseConfig::COST_MODE_POOL_SLOT);

            // The license form always posts this field (hidden 0 + checkbox 1),
            // so absence means a non-form caller; then keep the stored value,
            // defaulting a fresh config to on (the common floating use case).
            $config->allow_over_allocation = $request->has('floating_allow_over_allocation')
                ? $request->boolean('floating_allow_over_allocation')
                : (bool) ($config->allow_over_allocation ?? true);

            $config->deleted_at = null;
            $config->save();

            app(FloatingLicenseService::class)->recalculateCosts($config);

            return;
        }

        // Unchecked: remove the config, but never strand active allocations.
        $config = FloatingLicenseConfig::where('license_id', $license->id)->first();

        if (! $config) {
            return;
        }

        if ($config->activeAllocations()->count() > 0) {
            session()->flash('warning', trans('floating-licenses::floating.message.kept_active_allocations'));

            return;
        }

        $config->delete();
    }
}
