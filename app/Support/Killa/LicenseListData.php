<?php

namespace App\Support\Killa;

use App\Models\Company;
use App\Models\License;
use App\Models\LicenseSeat;
use App\Services\Killa\CurrencyConverter;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use SnipeIt\FloatingLicenses\Models\FloatingLicenseConfig;

/**
 * Server-side data pipeline for the [killa-ux] licenses list page. Recovers
 * and extends the row logic of the deleted RenewalTrackerController (git
 * e85d36876e): company-scoped licenses, floating flags, currency conversion,
 * sort_order-aware ordering within category groups.
 */
class LicenseListData
{
    public const PER_PAGE = 50;

    /**
     * @return array{kpis: array<string, mixed>, groups: Collection, paginator: LengthAwarePaginator,
     *               search: string, grouped: bool, base_currency: string}
     */
    public static function forIndex(Request $request): array
    {
        $search = trim((string) $request->input('search', ''));
        $grouped = $request->input('grouped', '1') !== '0';

        $query = Company::scopeCompanyables(License::query())
            ->with(['category', 'location', 'manufacturer'])
            ->withCount(['freeSeats as free_seats_count']);

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like) {
                $q->where('licenses.name', 'LIKE', $like)
                    ->orWhere('licenses.serial', 'LIKE', $like)
                    ->orWhereHas('category', fn ($cq) => $cq->where('name', 'LIKE', $like))
                    ->orWhereHas('manufacturer', fn ($mq) => $mq->where('name', 'LIKE', $like));
            });
        }

        $paginator = $query->orderBy('name')->paginate(self::PER_PAGE)->withQueryString();

        $floatingIds = FloatingLicenseConfig::query()->pluck('license_id')->flip();
        $checkinSeats = self::firstAssignedSeatIds($paginator->getCollection()->pluck('id'));
        $today = Carbon::today();

        $rows = $paginator->getCollection()->map(
            fn (License $license) => self::row($license, $floatingIds->has($license->id), $checkinSeats->get($license->id), $today)
        );

        $groups = $grouped
            ? $rows->groupBy('category')->sortKeys(SORT_NATURAL | SORT_FLAG_CASE)
                ->map(fn ($group) => self::sortGroup($group))
            : collect(['' => self::sortGroup($rows)]);

        return [
            'kpis' => self::kpis($today),
            'groups' => $groups,
            'paginator' => $paginator,
            'search' => $search,
            'grouped' => $grouped,
            'base_currency' => CurrencyConverter::base(),
        ];
    }

    /**
     * Urgency order within a group: manually ordered rows first (sort_order),
     * then soonest expiry, perpetual/no-expiry last, then name.
     */
    private static function sortGroup(Collection $rows): Collection
    {
        return $rows->sortBy([
            fn ($a, $b) => ($a['sort_order'] ?? PHP_INT_MAX) <=> ($b['sort_order'] ?? PHP_INT_MAX),
            fn ($a, $b) => ($a['days'] ?? PHP_INT_MAX) <=> ($b['days'] ?? PHP_INT_MAX),
            'name',
        ])->values();
    }

    /**
     * KPI strip figures, computed over the full company-scoped set (not just
     * the current page). Costs are normalized to the default currency; rows
     * whose currency has no configured rate are excluded from the sums.
     *
     * @return array{total: int, expiring_count: int, cost_at_risk: float, monthly_spend: float|null,
     *               seats_used: int, seats_available: int, seats_total: int, monthly_partial: bool}
     */
    private static function kpis(Carbon $today): array
    {
        $licenses = Company::scopeCompanyables(License::query())
            ->get(['id', 'seats', 'purchase_cost', 'currency', 'expiration_date', 'perpetual']);

        $licenseIds = $licenses->pluck('id');
        $seatsUsed = LicenseSeat::query()
            ->whereIn('license_id', $licenseIds)
            ->where(function ($q) {
                $q->whereNotNull('assigned_to')->orWhereNotNull('asset_id');
            })
            ->whereNull('deleted_at')
            ->count();

        $expiringCount = 0;
        $costAtRisk = 0.0;
        $monthlySpend = 0.0;
        $monthlyPartial = false;

        foreach ($licenses as $license) {
            $baseCost = self::baseCost($license);
            if ($license->purchase_cost !== null && $baseCost === null) {
                $monthlyPartial = true;
            }
            if ($baseCost !== null) {
                $monthlySpend += $baseCost / 12;
            }

            if (! $license->perpetual && $license->expiration_date) {
                $days = (int) $today->diffInDays($license->expiration_date, false);
                if ($days >= 0 && $days <= 60) {
                    $expiringCount++;
                    if ($baseCost !== null) {
                        $costAtRisk += $baseCost;
                    }
                }
            }
        }

        $seatsTotal = (int) $licenses->sum('seats');

        return [
            'total' => $licenses->count(),
            'expiring_count' => $expiringCount,
            'cost_at_risk' => $costAtRisk,
            'monthly_spend' => $monthlySpend,
            'monthly_partial' => $monthlyPartial,
            'seats_used' => $seatsUsed,
            'seats_available' => max(0, $seatsTotal - $seatsUsed),
            'seats_total' => $seatsTotal,
        ];
    }

    /**
     * Total yearly cost (per-seat × seats, server convention) normalized to
     * the default currency. Null when cost is unknown or no rate exists for
     * a foreign currency.
     */
    private static function baseCost(License $license): ?float
    {
        if ($license->purchase_cost === null) {
            return null;
        }

        $total = (float) $license->purchase_cost * max(1, (int) $license->seats);
        $currency = $license->currency ? strtoupper($license->currency) : CurrencyConverter::base();

        if ($currency === CurrencyConverter::base()) {
            return $total;
        }

        return CurrencyConverter::toBase($total, $currency);
    }

    /**
     * First assigned seat per license (for the row-level checkin quick action,
     * which needs a LicenseSeat id). One query for the whole page.
     *
     * @param  Collection<int, int>  $licenseIds
     * @return Collection<int, int> license_id => license_seat id
     */
    private static function firstAssignedSeatIds(Collection $licenseIds): Collection
    {
        if ($licenseIds->isEmpty()) {
            return collect();
        }

        return LicenseSeat::query()
            ->selectRaw('license_id, MIN(id) as seat_id')
            ->whereIn('license_id', $licenseIds)
            ->where(function ($q) {
                $q->whereNotNull('assigned_to')->orWhereNotNull('asset_id');
            })
            ->whereNull('deleted_at')
            ->groupBy('license_id')
            ->pluck('seat_id', 'license_id');
    }

    /**
     * @return array{license: License, id: int, sort_order: int|null, name: string, serial: string|null,
     *               manufacturer: string|null, url: string, edit_url: string, clone_url: string,
     *               checkout_url: string, checkin_url: string|null,
     *               location: string|null, category: string, perpetual: bool, floating: bool,
     *               term: string|null, seats: int, seats_used: int, seats_available: int,
     *               expiry: string|null, days: int|null, status: string,
     *               cost_monthly: float|null, cost_yearly: float|null, currency: string|null,
     *               cost_monthly_base: float|null, cost_yearly_base: float|null}
     */
    private static function row(License $license, bool $floating, ?int $checkinSeatId, Carbon $today): array
    {
        $expiry = $license->perpetual ? null : $license->expiration_date;
        $days = $expiry ? (int) $today->diffInDays($expiry, false) : null;

        $term = null;
        if ($license->purchase_date && $expiry) {
            $years = max(1, (int) round($license->purchase_date->diffInYears($expiry)));
            $term = $years === 1 ? trans('killa.licenses_ux.term_yearly') : trans('killa.licenses_ux.term_years', ['years' => $years]);
        }

        $status = 'none';
        if ($days !== null) {
            $status = $days <= 30 ? 'danger' : ($days <= 60 ? 'warning' : 'success');
        }

        // purchase_cost is per-seat (server convention); sheet costs are totals
        $cost = $license->purchase_cost !== null ? (float) $license->purchase_cost * max(1, (int) $license->seats) : null;
        $currency = $license->currency ? strtoupper($license->currency) : null;

        $seats = (int) $license->seats;
        $available = (int) ($license->free_seats_count ?? 0);

        return [
            'license' => $license,
            'id' => $license->id,
            'sort_order' => $license->sort_order !== null ? (int) $license->sort_order : null,
            'name' => $license->name,
            'serial' => $license->serial,
            'manufacturer' => $license->manufacturer?->name,
            'url' => route('licenses.show', $license),
            'edit_url' => route('licenses.edit', $license),
            'clone_url' => route('clone/license', $license),
            'checkout_url' => route('licenses.checkout', $license),
            'checkin_url' => $checkinSeatId ? route('licenses.checkin', $checkinSeatId) : null,
            'location' => $license->location?->name,
            'category' => $license->category?->name ?? trans('killa.licenses_ux.uncategorized'),
            'perpetual' => (bool) $license->perpetual,
            'floating' => $floating,
            'term' => $term,
            'seats' => $seats,
            'seats_used' => max(0, $seats - $available),
            'seats_available' => $available,
            'expiry' => $expiry?->format('d/m/Y'),
            'days' => $days,
            'status' => $status,
            'cost_monthly' => $cost !== null ? $cost / 12 : null,
            'cost_yearly' => $cost,
            'currency' => $currency,
            'cost_monthly_base' => $cost !== null ? CurrencyConverter::toBase($cost / 12, $currency ?? CurrencyConverter::base()) : null,
            'cost_yearly_base' => $cost !== null ? CurrencyConverter::toBase($cost, $currency ?? CurrencyConverter::base()) : null,
        ];
    }
}
