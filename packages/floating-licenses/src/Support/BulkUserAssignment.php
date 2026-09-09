<?php

namespace SnipeIt\FloatingLicenses\Support;

use App\Events\CheckoutableCheckedIn;
use App\Events\CheckoutableCheckedOut;
use App\Models\License;
use App\Models\LicenseSeat;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use SnipeIt\FloatingLicenses\Exceptions\InvalidAllocationException;
use SnipeIt\FloatingLicenses\Exceptions\PoolExhaustedException;
use SnipeIt\FloatingLicenses\Models\FloatingLicenseAllocation;
use SnipeIt\FloatingLicenses\Services\FloatingLicenseService;

class BulkUserAssignment
{
    public function __construct(public readonly FloatingLicenseService $service) {}

    /**
     * Bulk-assign a license to a set of users.
     *
     * Floating is strictly opt-in: only licenses with a persisted pool config
     * (FloatingLicenseSync::configForLicense()) allocate floating seats here.
     * Every other license falls back to the exact core seat-checkout
     * mechanism (free seat claimed under a row lock, CheckoutableCheckedOut
     * fired — same as LicenseCheckoutController::bulkFulfillStore()).
     *
     * @param  int[]  $userIds
     * @return array{added: int, skipped: int, failed: int}
     */
    public function addUsers(License $license, array $userIds): array
    {
        $config = FloatingLicenseSync::configForLicense($license);

        $added = 0;
        $skipped = 0;
        $failed = 0;

        foreach (array_unique($userIds) as $userId) {
            $user = User::find($userId);

            if (! $user) {
                $failed++;

                continue;
            }

            if ($config) {
                $alreadyActive = FloatingLicenseAllocation::where('license_id', $license->id)
                    ->where('user_id', $user->id)
                    ->active()
                    ->exists();

                if ($alreadyActive) {
                    $skipped++;

                    continue;
                }

                try {
                    $this->service->allocate($config, $user);
                    $added++;
                } catch (PoolExhaustedException) {
                    $failed++;
                }

                continue;
            }

            // Fixed-seat fallback (no floating config): core seat checkout.
            $alreadyAssigned = LicenseSeat::where('license_id', $license->id)
                ->where('assigned_to', $user->id)
                ->whereNull('deleted_at')
                ->exists();

            if ($alreadyAssigned) {
                $skipped++;

                continue;
            }

            if (! $this->assignSeatToUser($license, $user)) {
                $failed++;

                continue;
            }

            $added++;
        }

        return ['added' => $added, 'skipped' => $skipped, 'failed' => $failed];
    }

    /**
     * Claim a free license_seats row for a user under a row lock and fire
     * the core checkout event — the same mechanism the web checkout flow
     * uses. Returns the saved seat, or null when no seat was free or the
     * save failed. Shared by addUsers(), the CSV user import, and the
     * floating-to-standard conversion command.
     */
    public function assignSeatToUser(License $license, User $user, bool $allowUnreassignable = false): ?LicenseSeat
    {
        // CLI contexts (conversion command) have no authenticated user; fall
        // back to the first admin account for created_by / the event actor,
        // mirroring core's `auth()->id() ?: 1` convention.
        $actor = auth()->user() ?? User::orderBy('id')->first();

        $seat = DB::transaction(function () use ($license, $user, $actor, $allowUnreassignable): ?LicenseSeat {
            $seat = $license->freeSeat(lock: true);

            // Conversion fallback: a non-reassignable license accumulates
            // "burned" (unreassignable) but unassigned seat rows. When the
            // caller opts in, claim one of those so the user keeps their
            // assignment; the flag stays set, preserving the burn-on-checkin
            // semantics for future checkins.
            if (! $seat && $allowUnreassignable) {
                $seat = $license->licenseseats()
                    ->whereNull('deleted_at')
                    ->where('unreassignable_seat', '=', true)
                    ->whereNull('assigned_to')
                    ->whereNull('asset_id')
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->first();
            }

            if (! $seat) {
                return null;
            }

            $seat->assigned_to = $user->id;
            $seat->created_by = $actor?->id;

            return $seat->save() ? $seat : null;
        });

        if (! $seat) {
            return null;
        }

        event(new CheckoutableCheckedOut($seat, $user, $actor, trans('floating-licenses::floating.log.bulk_checkout')));

        return $seat;
    }

    /**
     * Bulk-remove a license from a set of users.
     *
     * Handles the mixed state: each user's active floating allocation is
     * released AND any core seat checked out to them is checked back in
     * (mirroring LicenseCheckinController::bulkCheckinSelected()), so a
     * license that accumulated seat checkouts before floating took over is
     * cleaned up by the same action.
     *
     * @param  int[]  $userIds
     * @return array{removed: int, skipped: int, failed: int}
     */
    public function removeUsers(License $license, array $userIds): array
    {
        $userIds = array_unique($userIds);

        $removed = 0;
        $failed = 0;
        $foundUserIds = [];

        $allocations = FloatingLicenseAllocation::where('license_id', $license->id)
            ->whereIn('user_id', $userIds)
            ->active()
            ->get();

        foreach ($allocations as $allocation) {
            $foundUserIds[$allocation->user_id] = true;

            try {
                $this->service->release($allocation, auth()->user());
                $removed++;
            } catch (InvalidAllocationException) {
                $failed++;
            }
        }

        $seats = LicenseSeat::where('license_id', $license->id)
            ->whereIn('assigned_to', $userIds)
            ->whereNull('deleted_at')
            ->with('user')
            ->get();

        foreach ($seats as $seat) {
            $foundUserIds[$seat->assigned_to] = true;
            $target = $seat->user;

            $seat->assigned_to = null;
            $seat->asset_id = null;
            if (! $license->reassignable) {
                $seat->unreassignable_seat = true;
            }

            if ($seat->save()) {
                event(new CheckoutableCheckedIn($seat, $target, auth()->user(), trans('floating-licenses::floating.log.bulk_checkin')));
                $removed++;
            } else {
                $failed++;
            }
        }

        return [
            'removed' => $removed,
            'skipped' => count(array_diff($userIds, array_keys($foundUserIds))),
            'failed' => $failed,
        ];
    }

    /**
     * Users currently holding the license via an active floating allocation.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function floatingAssignedUsers(License $license): \Illuminate\Support\Collection
    {
        return FloatingLicenseAllocation::where('license_id', $license->id)
            ->active()
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->values();
    }

    /**
     * Users currently holding the license via a core seat checkout.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function seatAssignedUsers(License $license): \Illuminate\Support\Collection
    {
        return LicenseSeat::where('license_id', $license->id)
            ->whereNotNull('assigned_to')
            ->whereNull('deleted_at')
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->values();
    }
}
