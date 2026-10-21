<?php

namespace App\Http\Controllers\Killa;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\License;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use SnipeIt\FloatingLicenses\Models\FloatingLicenseConfig;

class RenewalTrackerController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (! Gate::allows('view', License::class)) {
            Session::reflash();

            return Helper::safeIntended('account/view-assets');
        }

        $licenses = Company::scopeCompanyables(License::query())
            ->with('category')
            ->orderBy('name')
            ->get();

        $floatingIds = FloatingLicenseConfig::query()->pluck('license_id')->flip();

        $today = Carbon::today();
        $rows = $licenses->map(fn (License $license) => $this->row($license, $floatingIds->has($license->id), $today));

        $groups = $rows
            ->groupBy('category')
            ->sortKeys(SORT_NATURAL | SORT_FLAG_CASE)
            ->map(fn ($group) => $group->sortBy([
                // Urgency first: expired/soonest expiry at the top, perpetual last
                fn ($a, $b) => ($a['days'] ?? PHP_INT_MAX) <=> ($b['days'] ?? PHP_INT_MAX),
                'name',
            ])->values());

        return view('killa/renewals/index', ['groups' => $groups]);
    }

    /**
     * @return array{name: string, url: string, category: string, type: string,
     *               floating: bool, term: string|null, seats: int,
     *               expiry: string|null, days: int|null, status: string,
     *               cost_monthly: float|null, cost_yearly: float|null, notes: string|null}
     */
    private function row(License $license, bool $floating, Carbon $today): array
    {
        $expiry = $license->perpetual ? null : $license->expiration_date;
        $days = $expiry ? (int) $today->diffInDays($expiry, false) : null;

        $term = null;
        if ($license->purchase_date && $expiry) {
            $years = max(1, (int) round($license->purchase_date->diffInYears($expiry)));
            $term = $years === 1 ? trans('killa.renewals.term_yearly') : trans('killa.renewals.term_years', ['years' => $years]);
        }

        $status = 'none';
        if ($days !== null) {
            $status = $days <= 30 ? 'danger' : ($days <= 90 ? 'warning' : 'success');
        }

        $cost = $license->purchase_cost !== null ? (float) $license->purchase_cost : null;

        return [
            'name' => $license->name,
            'url' => route('licenses.show', $license),
            'category' => $license->category?->name ?? trans('killa.renewals.uncategorized'),
            'type' => $license->perpetual ? trans('killa.renewals.perpetual') : trans('killa.renewals.subscription'),
            'floating' => $floating,
            'term' => $term,
            'seats' => (int) $license->seats,
            'expiry' => $expiry?->format('d/m/Y'),
            'days' => $days,
            'status' => $status,
            'cost_monthly' => $cost !== null ? $cost / 12 : null,
            'cost_yearly' => $cost,
            'notes' => $license->notes,
        ];
    }
}
