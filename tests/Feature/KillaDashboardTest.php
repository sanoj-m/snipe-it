<?php

namespace Tests\Feature;

use App\Models\Accessory;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class KillaDashboardTest extends TestCase
{
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard.insights'))->assertRedirect(route('login'));
    }

    public function test_users_with_no_visibility_are_redirected_to_own_assets(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard.insights'))
            ->assertRedirect(route('view-assets'));
    }

    public function test_admin_sees_insights_dashboard_with_metrics_and_navigation(): void
    {
        Asset::factory()->count(2)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard.insights'))
            ->assertOk()
            ->assertViewIs('killa.dashboard')
            ->assertSee(trans('killa.insights_title'))
            ->assertSee(trans('general.dashboard'))
            ->assertSee(route('home'))
            ->assertSee('aria-current="page"', false)
            ->assertViewHas('metrics', function (array $metrics): bool {
                return $metrics['asset']['total'] === 2
                    && $metrics['asset']['available'] === 2
                    && $metrics['asset']['deployed'] === 0
                    && isset($metrics['license'], $metrics['accessory'], $metrics['consumable'], $metrics['component'], $metrics['user']);
            });
    }

    public function test_classic_dashboard_remains_available_and_links_to_insights(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('home'))
            ->assertOk()
            ->assertViewIs('dashboard')
            ->assertSee(route('dashboard.insights'))
            ->assertSee(trans('killa.classic'))
            ->assertSee(trans('killa.insights'));
    }

    public function test_asset_viewer_receives_only_asset_metrics_and_charts(): void
    {
        $this->actingAs(User::factory()->viewAssets()->create())
            ->get(route('dashboard.insights'))
            ->assertOk()
            ->assertViewHas('metrics', function (array $metrics): bool {
                return array_keys($metrics) === ['asset'];
            })
            ->assertViewHas('assetMovement')
            ->assertSee('id="assetMovementChart"', false)
            ->assertSee('id="statusPieChart"', false);
    }

    public function test_license_viewer_does_not_receive_asset_metrics_or_charts(): void
    {
        $this->actingAs(User::factory()->viewLicenses()->create())
            ->get(route('dashboard.insights'))
            ->assertOk()
            ->assertViewHas('metrics', function (array $metrics): bool {
                return array_keys($metrics) === ['license'];
            })
            ->assertViewHas('assetMovement', function (?array $movement): bool {
                return $movement === null;
            })
            ->assertDontSee('id="assetMovementChart"', false)
            ->assertDontSee('id="statusPieChart"', false);
    }

    public function test_asset_movement_contains_only_recent_asset_checkouts_and_checkins(): void
    {
        $asset = Asset::factory()->create();
        $accessory = Accessory::factory()->create();

        Actionlog::factory()->create([
            'item_id' => $asset->id,
            'item_type' => Asset::class,
            'action_type' => 'checkout',
            'created_at' => Carbon::today(),
        ]);
        Actionlog::factory()->create([
            'item_id' => $asset->id,
            'item_type' => Asset::class,
            'action_type' => 'checkin from',
            'created_at' => Carbon::today(),
        ]);
        Actionlog::factory()->create([
            'item_id' => $asset->id,
            'item_type' => Asset::class,
            'action_type' => 'checkin',
            'created_at' => Carbon::yesterday(),
        ]);
        Actionlog::factory()->create([
            'item_id' => $accessory->id,
            'item_type' => Accessory::class,
            'action_type' => 'checkout',
            'created_at' => Carbon::today(),
        ]);
        Actionlog::factory()->create([
            'item_id' => $asset->id,
            'item_type' => Asset::class,
            'action_type' => 'checkout',
            'created_at' => Carbon::today()->subDays(31),
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard.insights'))
            ->assertOk()
            ->assertViewHas('assetMovement', function (array $movement): bool {
                return count($movement['labels']) === 30
                    && $movement['checkout_total'] === 1
                    && $movement['checkin_total'] === 2
                    && $movement['checkouts'][29] === 1
                    && $movement['checkins'][29] === 1
                    && $movement['checkins'][28] === 1;
            });
    }

    public function test_empty_asset_movement_has_zero_series_and_an_empty_state(): void
    {
        $this->actingAs(User::factory()->viewAssets()->create())
            ->get(route('dashboard.insights'))
            ->assertOk()
            ->assertSee(trans('killa.no_asset_movement'))
            ->assertViewHas('assetMovement', function (array $movement): bool {
                return count($movement['labels']) === 30
                    && $movement['checkout_total'] === 0
                    && $movement['checkin_total'] === 0
                    && array_sum($movement['checkouts']) === 0
                    && array_sum($movement['checkins']) === 0;
            });
    }

    public function test_asset_metrics_and_movement_are_scoped_by_company(): void
    {
        $this->settings->enableMultipleFullCompanySupport();
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $assetA = Asset::factory()->create(['company_id' => $companyA->id]);
        $assetB = Asset::factory()->create(['company_id' => $companyB->id]);

        foreach ([$assetA, $assetB] as $asset) {
            Actionlog::factory()->create([
                'item_id' => $asset->id,
                'item_type' => Asset::class,
                'action_type' => 'checkout',
                'company_id' => $asset->company_id,
                'created_at' => Carbon::today(),
            ]);
        }

        $viewer = User::factory()->viewAssets()->forCompany($companyA)->create();

        $this->actingAs($viewer)
            ->get(route('dashboard.insights'))
            ->assertOk()
            ->assertViewHas('metrics', function (array $metrics): bool {
                return $metrics['asset']['total'] === 1;
            })
            ->assertViewHas('assetMovement', function (array $movement): bool {
                return $movement['checkout_total'] === 1;
            });
    }
}
