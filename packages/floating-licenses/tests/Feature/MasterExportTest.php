<?php

namespace SnipeIt\FloatingLicenses\Tests\Feature;

use App\Models\License;
use App\Models\User;
use SnipeIt\FloatingLicenses\Services\FloatingLicenseService;
use SnipeIt\FloatingLicenses\Tests\TestCase;

class MasterExportTest extends TestCase
{
    public function test_master_export_includes_license_info_and_assigned_users()
    {
        $admin = User::factory()->superuser()->create();

        $floatingLicense = License::factory()->create(['name' => 'Floating Thing', 'seats' => 5]);
        $config = $this->createFloatingConfig($floatingLicense, ['pool_size' => 5]);
        $floatingUser = User::factory()->create(['username' => 'float.user']);
        app(FloatingLicenseService::class)->allocate($config, $floatingUser);

        $standardLicense = License::factory()->create(['name' => 'Standard Thing', 'seats' => 2]);
        $seatUser = User::factory()->create(['username' => 'seat.user']);
        $seat = $standardLicense->freeSeat();
        $seat->assigned_to = $seatUser->id;
        $seat->save();

        $emptyLicense = License::factory()->create(['name' => 'Empty Thing', 'seats' => 1]);

        $response = $this->actingAs($admin)
            ->get(route('floating-licenses.licenses.export-full'));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('license_id', $content);
        $this->assertStringContainsString('Floating Thing', $content);
        $this->assertStringContainsString('float.user', $content);
        $this->assertStringContainsString('Standard Thing', $content);
        $this->assertStringContainsString('seat.user', $content);
        $this->assertStringContainsString('Empty Thing', $content);

        // Floating flag column reflects per-license opt-in.
        $lines = array_values(array_filter(explode("\n", $content)));
        $floatingRow = collect($lines)->first(fn ($line) => str_contains($line, 'Floating Thing'));
        $standardRow = collect($lines)->first(fn ($line) => str_contains($line, 'Standard Thing'));
        $this->assertStringContainsString(',yes,', $floatingRow);
        $this->assertStringContainsString(',no,', $standardRow);
    }

    public function test_master_export_requires_view_permission()
    {
        $this->actingAs(User::factory()->create())
            ->get(route('floating-licenses.licenses.export-full'))
            ->assertForbidden();
    }
}
