<?php

namespace SnipeIt\FloatingLicenses\Tests\Feature;

use App\Models\License;
use App\Models\LicenseSeat;
use App\Models\User;
use SnipeIt\FloatingLicenses\Models\FloatingLicenseAllocation;
use SnipeIt\FloatingLicenses\Models\FloatingLicenseConfig;
use SnipeIt\FloatingLicenses\Services\FloatingLicenseService;
use SnipeIt\FloatingLicenses\Tests\TestCase;

class ConvertFloatingLicensesToStandardTest extends TestCase
{
    private function allocateUsers(License $license, int $count): \Illuminate\Support\Collection
    {
        $config = FloatingLicenseConfig::where('license_id', $license->id)->firstOrFail();
        $service = app(FloatingLicenseService::class);

        $users = User::factory()->count($count)->create();
        foreach ($users as $user) {
            $service->allocate($config, $user);
        }

        return $users;
    }

    public function test_converts_floating_users_to_seat_checkouts_and_disables_floating()
    {
        $license = License::factory()->create(['seats' => 3]);
        $this->createFloatingConfig($license, ['pool_size' => 3]);
        $users = $this->allocateUsers($license, 2);

        $this->artisan('floating-licenses:convert-to-standard')->assertSuccessful();

        foreach ($users as $user) {
            $this->assertDatabaseHas('license_seats', [
                'license_id' => $license->id,
                'assigned_to' => $user->id,
            ]);
            $this->assertDatabaseHas('floating_license_allocations', [
                'license_id' => $license->id,
                'user_id' => $user->id,
                'status' => FloatingLicenseAllocation::STATUS_RELEASED,
            ]);
        }

        $this->assertEquals(0, FloatingLicenseAllocation::where('license_id', $license->id)->active()->count());
        $this->assertEquals(0, FloatingLicenseConfig::where('license_id', $license->id)->count(),
            'The config must be soft-deleted so the license becomes standard');
    }

    public function test_expands_seats_when_more_users_than_seats()
    {
        $license = License::factory()->create(['seats' => 1]);
        $this->createFloatingConfig($license, ['pool_size' => 1, 'allow_over_allocation' => true]);
        $users = $this->allocateUsers($license, 3);

        $this->artisan('floating-licenses:convert-to-standard')->assertSuccessful();

        $this->assertEquals(3, $license->fresh()->seats);
        foreach ($users as $user) {
            $this->assertDatabaseHas('license_seats', [
                'license_id' => $license->id,
                'assigned_to' => $user->id,
            ]);
        }
        $this->assertEquals(0, FloatingLicenseConfig::where('license_id', $license->id)->count());
    }

    public function test_skips_licenses_matching_the_except_option_case_insensitively()
    {
        $rhino = License::factory()->create(['name' => 'RHINO 8 for Teams', 'seats' => 2]);
        $this->createFloatingConfig($rhino, ['pool_size' => 2]);
        $this->allocateUsers($rhino, 1);

        $other = License::factory()->create(['name' => 'Adobe Photoshop', 'seats' => 2]);
        $this->createFloatingConfig($other, ['pool_size' => 2]);
        $this->allocateUsers($other, 1);

        $this->artisan('floating-licenses:convert-to-standard', ['--except' => 'rhino'])->assertSuccessful();

        $this->assertEquals(1, FloatingLicenseConfig::where('license_id', $rhino->id)->count(),
            'Rhino must stay floating');
        $this->assertEquals(1, FloatingLicenseAllocation::where('license_id', $rhino->id)->active()->count());
        $this->assertEquals(0, FloatingLicenseConfig::where('license_id', $other->id)->count());
    }

    public function test_dry_run_writes_nothing()
    {
        $license = License::factory()->create(['seats' => 2]);
        $this->createFloatingConfig($license, ['pool_size' => 2]);
        $this->allocateUsers($license, 1);

        $this->artisan('floating-licenses:convert-to-standard', ['--dry-run' => true])->assertSuccessful();

        $this->assertEquals(1, FloatingLicenseConfig::where('license_id', $license->id)->count());
        $this->assertEquals(1, FloatingLicenseAllocation::where('license_id', $license->id)->active()->count());
        $this->assertEquals(0, LicenseSeat::where('license_id', $license->id)->whereNotNull('assigned_to')->count());
    }

    public function test_does_not_double_assign_users_who_already_hold_a_seat()
    {
        $license = License::factory()->create(['seats' => 2]);
        $this->createFloatingConfig($license, ['pool_size' => 2]);

        $user = User::factory()->create();
        $seat = $license->freeSeat();
        $seat->assigned_to = $user->id;
        $seat->save();

        $config = FloatingLicenseConfig::where('license_id', $license->id)->firstOrFail();
        app(FloatingLicenseService::class)->allocate($config, $user);

        $this->artisan('floating-licenses:convert-to-standard')->assertSuccessful();

        $this->assertEquals(1, LicenseSeat::where('license_id', $license->id)
            ->where('assigned_to', $user->id)->count(),
            'The user must keep exactly one seat');
        $this->assertEquals(0, FloatingLicenseAllocation::where('license_id', $license->id)->active()->count());
        $this->assertEquals(0, FloatingLicenseConfig::where('license_id', $license->id)->count());
    }

    public function test_claims_burned_unreassignable_seats_so_users_keep_their_assignment()
    {
        // A non-reassignable license accumulates "burned" seat rows
        // (unreassignable_seat=true, unassigned) as users check in. The
        // conversion must still seat floating users on those rows.
        $license = License::factory()->create(['seats' => 2, 'reassignable' => 0]);
        $this->createFloatingConfig($license, ['pool_size' => 2, 'allow_over_allocation' => false]);

        $license->licenseseats()->whereNull('deleted_at')->update(['unreassignable_seat' => true]);
        $this->assertNull($license->freeSeat());

        $users = $this->allocateUsers($license, 2);

        $this->artisan('floating-licenses:convert-to-standard')->assertSuccessful();

        foreach ($users as $user) {
            $this->assertDatabaseHas('license_seats', [
                'license_id' => $license->id,
                'assigned_to' => $user->id,
            ]);
        }
        $this->assertEquals(0, FloatingLicenseConfig::where('license_id', $license->id)->count());
    }
}
