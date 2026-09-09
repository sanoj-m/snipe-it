<?php

namespace SnipeIt\FloatingLicenses\Tests\Feature;

use App\Models\License;
use App\Models\LicenseSeat;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use SnipeIt\FloatingLicenses\Models\FloatingLicenseAllocation;
use SnipeIt\FloatingLicenses\Tests\TestCase;

class LicenseUsersImportExportTest extends TestCase
{
    public function test_export_lists_seat_and_floating_users()
    {
        $license = License::factory()->create(['seats' => 3]);
        $config = $this->createFloatingConfig($license, ['pool_size' => 3]);
        $admin = User::factory()->superuser()->create();

        $seatUser = User::factory()->create(['username' => 'seat.user']);
        $seat = $license->freeSeat();
        $seat->assigned_to = $seatUser->id;
        $seat->save();

        $floatingUser = User::factory()->create(['username' => 'floating.user']);
        app(\SnipeIt\FloatingLicenses\Services\FloatingLicenseService::class)->allocate($config, $floatingUser);

        $response = $this->actingAs($admin)
            ->get(route('floating-licenses.license.users-export', $license));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('seat.user', $content);
        $this->assertStringContainsString('floating.user', $content);
        $this->assertStringContainsString('assignment_type', $content);
    }

    public function test_import_assigns_users_to_a_floating_license()
    {
        $license = License::factory()->create(['seats' => 5]);
        $this->createFloatingConfig($license, ['pool_size' => 5]);
        $admin = User::factory()->superuser()->create();
        $users = User::factory()->count(2)->create();

        $csv = "username\n".$users[0]->username."\n".$users[1]->username."\n";
        $file = UploadedFile::fake()->createWithContent('users.csv', $csv);

        $this->actingAs($admin)
            ->post(route('floating-licenses.license.users-import', $license), ['user_list' => $file])
            ->assertRedirect(route('licenses.show', $license))
            ->assertSessionHas('success');

        $this->assertEquals(2, FloatingLicenseAllocation::where('license_id', $license->id)->active()->count());
    }

    public function test_import_assigns_users_to_a_standard_license_via_seats()
    {
        $license = License::factory()->create(['seats' => 2]);
        $admin = User::factory()->superuser()->create();
        $users = User::factory()->count(2)->create();

        $csv = implode("\n", $users->pluck('email')->all());
        $file = UploadedFile::fake()->createWithContent('users.csv', $csv);

        $this->actingAs($admin)
            ->post(route('floating-licenses.license.users-import', $license), ['user_list' => $file])
            ->assertRedirect(route('licenses.show', $license))
            ->assertSessionHas('success');

        foreach ($users as $user) {
            $this->assertDatabaseHas('license_seats', [
                'license_id' => $license->id,
                'assigned_to' => $user->id,
            ]);
        }
    }

    public function test_import_reports_unknown_identifiers_and_skips_header()
    {
        $license = License::factory()->create(['seats' => 2]);
        $admin = User::factory()->superuser()->create();
        $user = User::factory()->create();

        $csv = "username\n".$user->username."\nno.such.user\n";
        $file = UploadedFile::fake()->createWithContent('users.csv', $csv);

        $this->actingAs($admin)
            ->post(route('floating-licenses.license.users-import', $license), ['user_list' => $file])
            ->assertRedirect(route('licenses.show', $license))
            ->assertSessionHas('warning');

        $this->assertDatabaseHas('license_seats', [
            'license_id' => $license->id,
            'assigned_to' => $user->id,
        ]);
        $this->assertEquals(1, LicenseSeat::where('license_id', $license->id)->whereNotNull('assigned_to')->count());
    }

    public function test_import_matches_full_names_and_tolerates_bom_and_user_header()
    {
        $license = License::factory()->create(['seats' => 3]);
        $admin = User::factory()->superuser()->create();
        $user = User::factory()->create(['first_name' => 'Daria', 'last_name' => 'Morrison']);

        // Excel-style file: UTF-8 BOM, a "User" header, one display name per line.
        $csv = "\xEF\xBB\xBFUser\nDaria Morrison\n";
        $file = UploadedFile::fake()->createWithContent('users.csv', $csv);

        $this->actingAs($admin)
            ->post(route('floating-licenses.license.users-import', $license), ['user_list' => $file])
            ->assertRedirect(route('licenses.show', $license))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('license_seats', [
            'license_id' => $license->id,
            'assigned_to' => $user->id,
        ]);
    }

    public function test_import_uses_username_column_from_export_files()
    {
        $license = License::factory()->create(['seats' => 3]);
        $admin = User::factory()->superuser()->create();
        $user = User::factory()->create(['username' => 'export.user']);

        // Shape of the per-license export: username first, then name columns.
        $csv = "username,first_name,last_name,email,assignment_type,assigned_at\n"
            ."export.user,Export,User,export.user@example.test,seat,2026-01-01 00:00:00\n";
        $file = UploadedFile::fake()->createWithContent('users.csv', $csv);

        $this->actingAs($admin)
            ->post(route('floating-licenses.license.users-import', $license), ['user_list' => $file])
            ->assertRedirect(route('licenses.show', $license))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('license_seats', [
            'license_id' => $license->id,
            'assigned_to' => $user->id,
        ]);
    }

    public function test_export_requires_view_permission()
    {
        $license = License::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('floating-licenses.license.users-export', $license))
            ->assertForbidden();
    }
}
