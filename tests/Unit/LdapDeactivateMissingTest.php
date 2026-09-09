<?php

namespace Tests\Unit;

use App\Models\Ldap;
use App\Models\User;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('ldap')]
class LdapDeactivateMissingTest extends TestCase
{
    public function test_deactivates_ldap_imported_user_missing_from_sync_results()
    {
        $user = User::factory()->create([
            'ldap_import' => 1,
            'activated' => 1,
            'username' => 'gone.from.ldap',
        ]);

        $summary = Ldap::deactivateUsersMissingFromLdap(['still.here']);

        $this->assertSame(0, (int) $user->fresh()->activated);
        $this->assertCount(1, $summary);
        $this->assertSame('gone.from.ldap', $summary[0]['username']);
        $this->assertSame('deactivated', $summary[0]['createorupdate']);
        $this->assertSame('success', $summary[0]['status']);
        $this->assertSame('deactivated_missing_from_ldap', $summary[0]['note']);
    }

    public function test_leaves_users_still_present_in_sync_results_alone()
    {
        $user = User::factory()->create([
            'ldap_import' => 1,
            'activated' => 1,
            'username' => 'still.here',
        ]);

        $summary = Ldap::deactivateUsersMissingFromLdap(['still.here']);

        $this->assertSame(1, (int) $user->fresh()->activated);
        $this->assertCount(0, $summary);
    }

    public function test_leaves_non_ldap_users_alone()
    {
        $user = User::factory()->create([
            'ldap_import' => 0,
            'activated' => 1,
            'username' => 'local.admin',
        ]);

        $summary = Ldap::deactivateUsersMissingFromLdap([]);

        $this->assertSame(1, (int) $user->fresh()->activated);
        $this->assertCount(0, $summary);
    }

    public function test_leaves_already_deactivated_users_alone()
    {
        $user = User::factory()->create([
            'ldap_import' => 1,
            'activated' => 0,
            'username' => 'already.off',
        ]);

        $summary = Ldap::deactivateUsersMissingFromLdap([]);

        $this->assertSame(0, (int) $user->fresh()->activated);
        $this->assertCount(0, $summary);
    }
}
