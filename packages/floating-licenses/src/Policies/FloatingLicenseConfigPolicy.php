<?php

namespace SnipeIt\FloatingLicenses\Policies;

use App\Models\User;
use SnipeIt\FloatingLicenses\Models\FloatingLicenseConfig;

/**
 * Policy for floating license pool configs, mapping policy abilities onto
 * the package's existing permission strings.
 *
 * The string gates (`floating_licenses.view` etc.) remain defined in
 * FloatingLicensesServiceProvider: they feed the runtime-merged
 * config('permissions') UI and the non-model authorize() calls
 * (license-bound routes, the own-vs-admin release/heartbeat ownership
 * split). This policy covers the model-level checks so controllers can use
 * `$this->authorize('update', $config)` like core controllers do. Superusers
 * still pass via the global Gate::before() in AuthServiceProvider; FMCS
 * scoping stays in the controllers (configs carry no company_id, so
 * Company::isCurrentUserHasAccess() cannot scope them).
 */
class FloatingLicenseConfigPolicy
{
    /**
     * Determine whether the user can list pool configs.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAccess('floating_licenses.view');
    }

    /**
     * Determine whether the user can view a pool config.
     */
    public function view(User $user, ?FloatingLicenseConfig $config = null): bool
    {
        return $user->hasAccess('floating_licenses.view');
    }

    /**
     * Determine whether the user can enable floating licensing on a license.
     */
    public function create(User $user): bool
    {
        return $user->hasAccess('floating_licenses.manage');
    }

    /**
     * Determine whether the user can edit a pool config.
     */
    public function update(User $user, ?FloatingLicenseConfig $config = null): bool
    {
        return $user->hasAccess('floating_licenses.manage');
    }

    /**
     * Determine whether the user can disable (delete) a pool config.
     */
    public function delete(User $user, ?FloatingLicenseConfig $config = null): bool
    {
        return $user->hasAccess('floating_licenses.manage');
    }

    /**
     * Determine whether the user can allocate slots from a pool.
     */
    public function allocate(User $user, ?FloatingLicenseConfig $config = null): bool
    {
        return $user->hasAccess('floating_licenses.allocate');
    }

    /**
     * Determine whether the user can release any user's allocation.
     */
    public function release(User $user, ?FloatingLicenseConfig $config = null): bool
    {
        return $user->hasAccess('floating_licenses.release');
    }
}
