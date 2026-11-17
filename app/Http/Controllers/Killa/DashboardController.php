<?php

namespace App\Http\Controllers\Killa;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Accessory;
use App\Models\AccessoryCheckout;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Component;
use App\Models\Consumable;
use App\Models\License;
use App\Models\LicenseSeat;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;

class DashboardController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (! Gate::allows('canViewUsersAndCheckoutables')) {
            Session::reflash();

            return Helper::safeIntended('account/view-assets');
        }

        $metrics = [];
        $canViewAssets = Gate::allows('view', Asset::class);

        if ($canViewAssets) {
            $assets = Asset::AssetsForShow();
            $metrics['asset'] = [
                'total' => (clone $assets)->count(),
                'deployed' => (clone $assets)->Deployed()->count(),
                'available' => (clone $assets)->RTD()->count(),
            ];
        }

        if (Gate::allows('view', License::class)) {
            $metrics['license'] = [
                'total' => License::assetcount(),
                'free' => LicenseSeat::whereNull('asset_id')
                    ->whereNull('assigned_to')
                    ->where('unreassignable_seat', false)
                    ->whereNull('deleted_at')
                    ->count(),
                'expiring' => License::expiringLicenses(
                    (int) (Setting::getSettings()->alert_interval ?: 30)
                )->count(),
            ];
        }

        if (Gate::allows('view', Accessory::class)) {
            $metrics['accessory'] = [
                'total' => Accessory::count(),
                'remaining' => (int) Accessory::sum('qty') - AccessoryCheckout::count(),
            ];
        }

        if (Gate::allows('view', Consumable::class)) {
            $metrics['consumable'] = [
                'total' => Consumable::count(),
                'low' => Consumable::where('min_amt', '>', 0)
                    ->whereColumn('qty', '<', 'min_amt')
                    ->count(),
            ];
        }

        if (Gate::allows('view', Component::class)) {
            $metrics['component'] = [
                'total' => Component::count(),
                'remaining' => (int) Component::sum('qty')
                    - (int) DB::table('components_assets')->sum('assigned_qty'),
            ];
        }

        if (Gate::allows('view', User::class)) {
            $users = Company::scopeCompanyables(User::query());
            $metrics['user'] = [
                'total' => (clone $users)->count(),
                'with_items' => $users->where(function ($query) {
                    $query->has('assets')->orHas('licenses')->orHas('accessories')->orHas('consumables');
                })->count(),
            ];
        }

        return view('killa.dashboard', [
            'metrics' => $metrics,
            'assetMovement' => $canViewAssets ? $this->assetMovement() : null,
        ]);
    }

    private function assetMovement(): array
    {
        $start = Carbon::today()->subDays(29)->startOfDay();
        $end = Carbon::today()->endOfDay();
        $dates = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dates[] = $date->toDateString();
        }

        $checkouts = $this->movementCounts(['checkout'], $start, $end);
        $checkins = $this->movementCounts(['checkin', 'checkin from'], $start, $end);
        $fill = static fn (array $counts): array => array_map(
            static fn (string $date): int => (int) ($counts[$date] ?? 0),
            $dates
        );
        $checkoutSeries = $fill($checkouts);
        $checkinSeries = $fill($checkins);

        return [
            'labels' => array_map(
                static fn (string $date): string => Carbon::parse($date)->format('M j'),
                $dates
            ),
            'checkouts' => $checkoutSeries,
            'checkins' => $checkinSeries,
            'checkout_total' => array_sum($checkoutSeries),
            'checkin_total' => array_sum($checkinSeries),
        ];
    }

    private function movementCounts(array $actionTypes, Carbon $start, Carbon $end): array
    {
        return Actionlog::query()
            ->where('item_type', Asset::class)
            ->whereIn('action_type', $actionTypes)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();
    }
}
