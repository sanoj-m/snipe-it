<?php

namespace Tests\Feature\Licenses;

use App\Models\Category;
use App\Models\Company;
use App\Models\License;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class RenewalTrackerTest extends TestCase
{
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('licenses.renewals'))->assertRedirect(route('login'));
    }

    public function test_users_without_license_permission_are_redirected(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('licenses.renewals'))
            ->assertRedirect(route('view-assets'));
    }

    public function test_license_viewer_sees_grouped_renewals(): void
    {
        $category = Category::factory()->create(['name' => 'Autodesk', 'category_type' => 'license']);
        License::factory()->create([
            'name' => 'AutoCAD',
            'category_id' => $category->id,
            'expiration_date' => Carbon::today()->addDays(58),
            'purchase_date' => Carbon::today()->subYear(),
            'purchase_cost' => 1200,
        ]);

        $this->actingAs(User::factory()->viewLicenses()->create())
            ->get(route('licenses.renewals'))
            ->assertOk()
            ->assertViewIs('killa.renewals.index')
            ->assertSee(trans('killa.renewals.title'))
            ->assertSee('Autodesk')
            ->assertSee('AutoCAD')
            ->assertSee('58');
    }

    public function test_perpetual_license_shows_no_expiry(): void
    {
        License::factory()->create([
            'name' => 'Windows 10',
            'perpetual' => true,
            'expiration_date' => null,
        ]);

        $this->actingAs(User::factory()->viewLicenses()->create())
            ->get(route('licenses.renewals'))
            ->assertOk()
            ->assertSee('Windows 10')
            ->assertSee(trans('killa.renewals.perpetual'));
    }

    public function test_expired_license_is_marked_expired(): void
    {
        License::factory()->create([
            'name' => 'OldSoft',
            'expiration_date' => Carbon::today()->subDays(10),
        ]);

        $this->actingAs(User::factory()->viewLicenses()->create())
            ->get(route('licenses.renewals'))
            ->assertOk()
            ->assertSee('OldSoft')
            ->assertSee(trans('killa.renewals.expired'));
    }

    public function test_rows_are_scoped_by_company_under_fmcs(): void
    {
        $this->settings->enableMultipleFullCompanySupport();
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        License::factory()->create(['name' => 'MineSoft', 'company_id' => $companyA->id]);
        License::factory()->create(['name' => 'TheirSoft', 'company_id' => $companyB->id]);

        $viewer = User::factory()->viewLicenses()->forCompany($companyA)->create();

        $this->actingAs($viewer)
            ->get(route('licenses.renewals'))
            ->assertOk()
            ->assertSee('MineSoft')
            ->assertDontSee('TheirSoft');
    }
}
