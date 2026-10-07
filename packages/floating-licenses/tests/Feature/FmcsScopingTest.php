<?php

namespace SnipeIt\FloatingLicenses\Tests\Feature;

use App\Models\Company;
use App\Models\License;
use App\Models\User;
use SnipeIt\FloatingLicenses\Models\FloatingLicenseAllocation;
use SnipeIt\FloatingLicenses\Tests\TestCase;

class FmcsScopingTest extends TestCase
{
    protected function createUserWithPermissionsInCompany(Company $company, array $permissions): User
    {
        $grants = [];
        foreach ($permissions as $permission) {
            $grants[$permission] = '1';
        }

        return User::factory()->forCompany($company)->create(['permissions' => json_encode($grants)]);
    }

    public function test_index_hides_other_companies_pools_under_fmcs()
    {
        $this->settings->enableMultipleFullCompanySupport();

        [$companyA, $companyB] = Company::factory()->count(2)->create();
        $licenseA = License::factory()->for($companyA)->create(['name' => 'CompanyAlphaPool']);
        $licenseB = License::factory()->for($companyB)->create(['name' => 'CompanyBetaPool']);
        $this->createFloatingConfig($licenseA);
        $this->createFloatingConfig($licenseB);

        $viewer = $this->createUserWithPermissionsInCompany($companyA, ['floating_licenses.view']);

        $this->actingAs($viewer)
            ->get(route('floating-licenses.index'))
            ->assertOk()
            ->assertSee('CompanyAlphaPool')
            ->assertDontSee('CompanyBetaPool');
    }

    public function test_index_shows_all_pools_when_fmcs_is_off()
    {
        [$companyA, $companyB] = Company::factory()->count(2)->create();
        $licenseA = License::factory()->for($companyA)->create(['name' => 'CompanyAlphaPool']);
        $licenseB = License::factory()->for($companyB)->create(['name' => 'CompanyBetaPool']);
        $this->createFloatingConfig($licenseA);
        $this->createFloatingConfig($licenseB);

        $viewer = $this->createUserWithPermissionsInCompany($companyA, ['floating_licenses.view']);

        $this->actingAs($viewer)
            ->get(route('floating-licenses.index'))
            ->assertOk()
            ->assertSee('CompanyAlphaPool')
            ->assertSee('CompanyBetaPool');
    }

    public function test_show_of_other_companys_pool_is_not_found_under_fmcs()
    {
        $this->settings->enableMultipleFullCompanySupport();

        [$companyA, $companyB] = Company::factory()->count(2)->create();
        $configB = $this->createFloatingConfig(License::factory()->for($companyB)->create());

        $viewer = $this->createUserWithPermissionsInCompany($companyA, ['floating_licenses.view']);

        $this->actingAs($viewer)
            ->get(route('floating-licenses.show', $configB))
            ->assertNotFound();
    }

    public function test_edit_of_other_companys_pool_is_not_found_under_fmcs()
    {
        $this->settings->enableMultipleFullCompanySupport();

        [$companyA, $companyB] = Company::factory()->count(2)->create();
        $configB = $this->createFloatingConfig(License::factory()->for($companyB)->create());

        $manager = $this->createUserWithPermissionsInCompany($companyA, ['floating_licenses.manage']);

        $this->actingAs($manager)
            ->get(route('floating-licenses.edit', $configB))
            ->assertNotFound();
    }

    public function test_allocate_rejects_user_from_another_company_under_fmcs()
    {
        $this->settings->enableMultipleFullCompanySupport();

        [$companyA, $companyB] = Company::factory()->count(2)->create();
        $licenseA = License::factory()->for($companyA)->create();
        $configA = $this->createFloatingConfig($licenseA);
        $admin = $this->createUserWithPermissionsInCompany($companyA, ['floating_licenses.allocate']);
        $targetFromB = User::factory()->forCompany($companyB)->create();

        $this->actingAs($admin)
            ->post(route('floating-licenses.allocate', $configA), ['user_id' => $targetFromB->id])
            ->assertRedirect(route('floating-licenses.show', $configA))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('floating_license_allocations', [
            'license_id' => $licenseA->id,
            'user_id' => $targetFromB->id,
        ]);
    }

    public function test_bulk_add_rejects_user_from_another_company_under_fmcs()
    {
        $this->settings->enableMultipleFullCompanySupport();

        [$companyA, $companyB] = Company::factory()->count(2)->create();
        $licenseA = License::factory()->for($companyA)->create();
        $this->createFloatingConfig($licenseA);
        $admin = $this->createUserWithPermissionsInCompany($companyA, ['floating_licenses.allocate']);
        $targetFromA = User::factory()->forCompany($companyA)->create();
        $targetFromB = User::factory()->forCompany($companyB)->create();

        $this->actingAs($admin)
            ->post(route('floating-licenses.license.bulk-add', $licenseA), [
                'user_ids' => [$targetFromA->id, $targetFromB->id],
            ])
            ->assertRedirect(route('licenses.show', $licenseA))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('floating_license_allocations', [
            'license_id' => $licenseA->id,
            'user_id' => $targetFromB->id,
        ]);
    }

    public function test_api_availability_of_other_companys_license_is_not_found_under_fmcs()
    {
        $this->settings->enableMultipleFullCompanySupport();

        [$companyA, $companyB] = Company::factory()->count(2)->create();
        $licenseB = License::factory()->for($companyB)->create();
        $this->createFloatingConfig($licenseB);

        $viewer = $this->createUserWithPermissionsInCompany($companyA, ['floating_licenses.view']);

        $this->actingAsForApi($viewer)
            ->getJson(route('api.floating-licenses.availability', $licenseB))
            ->assertNotFound();
    }

    public function test_api_allocate_rejects_user_from_another_company_under_fmcs()
    {
        $this->settings->enableMultipleFullCompanySupport();

        [$companyA, $companyB] = Company::factory()->count(2)->create();
        $licenseA = License::factory()->for($companyA)->create();
        $this->createFloatingConfig($licenseA);
        $admin = $this->createUserWithPermissionsInCompany($companyA, ['floating_licenses.allocate']);
        $targetFromB = User::factory()->forCompany($companyB)->create();

        $this->actingAsForApi($admin)
            ->postJson(route('api.floating-licenses.allocate', $licenseA), ['user_id' => $targetFromB->id])
            ->assertStatus(422);

        $this->assertDatabaseMissing('floating_license_allocations', [
            'license_id' => $licenseA->id,
            'user_id' => $targetFromB->id,
        ]);
    }

    public function test_allocate_to_same_company_user_still_works_under_fmcs()
    {
        $this->settings->enableMultipleFullCompanySupport();

        $companyA = Company::factory()->create();
        $licenseA = License::factory()->for($companyA)->create();
        $configA = $this->createFloatingConfig($licenseA);
        $admin = $this->createUserWithPermissionsInCompany($companyA, ['floating_licenses.allocate']);
        $targetFromA = User::factory()->forCompany($companyA)->create();

        $this->actingAs($admin)
            ->post(route('floating-licenses.allocate', $configA), ['user_id' => $targetFromA->id])
            ->assertRedirect(route('floating-licenses.show', $configA))
            ->assertSessionHas('success');

        $this->assertEquals(1, FloatingLicenseAllocation::where('license_id', $licenseA->id)->active()->count());
    }

    public function test_superuser_still_sees_all_pools_under_fmcs()
    {
        $this->settings->enableMultipleFullCompanySupport();

        [$companyA, $companyB] = Company::factory()->count(2)->create();
        $licenseA = License::factory()->for($companyA)->create(['name' => 'CompanyAlphaPool']);
        $licenseB = License::factory()->for($companyB)->create(['name' => 'CompanyBetaPool']);
        $this->createFloatingConfig($licenseA);
        $this->createFloatingConfig($licenseB);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('floating-licenses.index'))
            ->assertOk()
            ->assertSee('CompanyAlphaPool')
            ->assertSee('CompanyBetaPool');
    }
}
