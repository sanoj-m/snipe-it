<?php

namespace SnipeIt\FloatingLicenses\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use SnipeIt\FloatingLicenses\Tests\TestCase;

class PolicyTest extends TestCase
{
    public function test_view_any_maps_to_view_permission()
    {
        $config = $this->createFloatingConfig();

        $viewer = $this->createUserWithFloatingPermissions(['floating_licenses.view']);
        $outsider = User::factory()->create();

        $this->assertTrue(Gate::forUser($viewer)->allows('viewAny', get_class($config)));
        $this->assertTrue(Gate::forUser($viewer)->allows('view', $config));
        $this->assertFalse(Gate::forUser($outsider)->allows('viewAny', get_class($config)));
        $this->assertFalse(Gate::forUser($outsider)->allows('view', $config));
    }

    public function test_create_update_delete_map_to_manage_permission()
    {
        $config = $this->createFloatingConfig();

        $manager = $this->createUserWithFloatingPermissions(['floating_licenses.manage']);
        $viewer = $this->createUserWithFloatingPermissions(['floating_licenses.view']);

        $this->assertTrue(Gate::forUser($manager)->allows('create', get_class($config)));
        $this->assertTrue(Gate::forUser($manager)->allows('update', $config));
        $this->assertTrue(Gate::forUser($manager)->allows('delete', $config));
        $this->assertFalse(Gate::forUser($viewer)->allows('create', get_class($config)));
        $this->assertFalse(Gate::forUser($viewer)->allows('update', $config));
        $this->assertFalse(Gate::forUser($viewer)->allows('delete', $config));
    }

    public function test_allocate_and_release_map_to_their_own_permissions()
    {
        $config = $this->createFloatingConfig();

        $allocator = $this->createUserWithFloatingPermissions(['floating_licenses.allocate']);
        $releaser = $this->createUserWithFloatingPermissions(['floating_licenses.release']);

        $this->assertTrue(Gate::forUser($allocator)->allows('allocate', $config));
        $this->assertFalse(Gate::forUser($allocator)->allows('release', $config));
        $this->assertFalse(Gate::forUser($releaser)->allows('allocate', $config));
        $this->assertTrue(Gate::forUser($releaser)->allows('release', $config));
    }

    public function test_superuser_passes_policy_checks()
    {
        $config = $this->createFloatingConfig();
        $superuser = User::factory()->superuser()->create();

        $this->assertTrue(Gate::forUser($superuser)->allows('update', $config));
        $this->assertTrue(Gate::forUser($superuser)->allows('allocate', $config));
        $this->assertTrue(Gate::forUser($superuser)->allows('release', $config));
    }
}
