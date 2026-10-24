<?php

namespace App\Support\Killa;

use App\Helpers\Helper;
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
            ->with(['category', 'location', 'manufacturer', 'company', 'supplier', 'depreciation', 'adminuser'])
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

        $pageIds = $paginator->getCollection()->pluck('id');
        $floatingIds = FloatingLicenseConfig::query()->pluck('license_id')->flip();
        // [floating-licenses parity] pool configs + active allocation counts for
        // the page, so seat math matches LicensesTransformer (two grouped
        // queries, no N+1).
        $floatingConfigs = FloatingLicenseConfig::query()->whereIn('license_id', $pageIds)->get()->keyBy('license_id');
        $floatingActive = collect();
        if ($floatingConfigs->isNotEmpty()) {
            $floatingActive = \SnipeIt\FloatingLicenses\Models\FloatingLicenseAllocation::query()
                ->whereIn('license_id', $floatingConfigs->keys())
                ->where('status', \SnipeIt\FloatingLicenses\Models\FloatingLicenseAllocation::STATUS_ACTIVE)
                ->groupBy('license_id')
                ->selectRaw('license_id, COUNT(*) as active_count')
                ->pluck('active_count', 'license_id');
        }
        $checkinSeats = self::firstAssignedSeatIds($pageIds);
        $today = Carbon::today();

        $rows = $paginator->getCollection()->map(
            fn (License $license) => self::row(
                $license,
                $floatingIds->has($license->id),
                $checkinSeats->get($license->id),
                $today,
                $floatingConfigs->get($license->id),
                (int) ($floatingActive[$license->id] ?? 0),
            )
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
     *               product_key: string|null, license_type: string,
     *               manufacturer: string|null, manufacturer_url: string|null,
     *               company: string|null, company_url: string|null,
     *               supplier: string|null, supplier_url: string|null,
     *               category_url: string|null, depreciation: string|null, depreciation_url: string|null,
     *               created_by: string|null, created_by_url: string|null,
     *               url: string, edit_url: string, clone_url: string,
     *               checkout_url: string, checkin_url: string|null,
     *               location: string|null, category: string, perpetual: bool, floating: bool,
     *               term: string|null, seats: int, seats_used: int, seats_available: int,
     *               remaining: int, percent_remaining: int, min_amt: int|null,
     *               expiry: string|null, expiration_formatted: string|null, days: int|null, status: string,
     *               termination_formatted: string|null, purchase_date_formatted: string|null,
     *               created_at_formatted: string|null, updated_at_formatted: string|null,
     *               license_email: string|null, license_name: string|null,
     *               maintained: bool, reassignable: bool, requestable: bool,
     *               order_number: string|null, purchase_order: string|null, notes_html: string|null,
     *               unit_cost: string|null, total_cost: string|null, per_user_cost: string|null,
     *               cost_monthly: float|null, cost_yearly: float|null, currency: string|null,
     *               cost_monthly_base: float|null, cost_yearly_base: float|null}
     */
    private static function row(License $license, bool $floating, ?int $checkinSeatId, Carbon $today, ?\SnipeIt\FloatingLicenses\Models\FloatingLicenseConfig $floatingConfig = null, int $floatingActiveCount = 0): array
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
        $unreassignable = $license->reassignable ? 0 : (int) License::unReassignableCount($license);
        $available = (int) ($license->free_seats_count ?? 0);

        // [floating-licenses parity] core seat math ignores floating
        // allocations; with a pool, available = seats - active allocations
        // (may go negative when over-allocated — matches LicensesTransformer).
        if ($floatingConfig) {
            $available = $seats - $floatingActiveCount;
        }

        $percentRemaining = $seats > 0 ? max(0, min(100, (int) round(($available / $seats) * 100))) : 0;

        $perUserCost = null;
        if ($floatingConfig) {
            $perUserCost = ($floatingConfig->total_cost && $floatingActiveCount > 0)
                ? Helper::formatCurrencyOutput(round(((float) $floatingConfig->total_cost) / $floatingActiveCount, 2))
                : null;
        } else {
            $perUserCost = $license->purchase_cost !== null ? Helper::formatCurrencyOutput($license->purchase_cost) : null;
        }

        $canViewKeys = \Illuminate\Support\Facades\Gate::allows('viewKeys', $license);

        $fmtDate = fn ($date) => $date ? (Helper::getFormattedDateObject($date, 'date')['formatted'] ?? null) : null;

        return [
            'license' => $license,
            'id' => $license->id,
            'sort_order' => $license->sort_order !== null ? (int) $license->sort_order : null,
            'name' => $license->name,
            'serial' => $license->serial,
            'product_key' => $license->serial
                ? ($canViewKeys ? $license->serial : License::PRODUCT_KEY_MASK)
                : null,
            'license_type' => $floating
                ? trans('floating-licenses::floating.type_floating')
                : trans('floating-licenses::floating.type_fixed'),
            'manufacturer' => $license->manufacturer?->name,
            'manufacturer_url' => $license->manufacturer ? route('manufacturers.show', $license->manufacturer) : null,
            'company' => $license->company?->name,
            'company_url' => $license->company ? route('companies.show', $license->company) : null,
            'supplier' => $license->supplier?->name,
            'supplier_url' => $license->supplier ? route('suppliers.show', $license->supplier) : null,
            'category_url' => $license->category ? route('categories.show', $license->category) : null,
            'depreciation' => $license->depreciation?->name,
            'depreciation_url' => $license->depreciation ? route('depreciations.show', $license->depreciation) : null,
            'created_by' => $license->adminuser?->display_name,
            'created_by_url' => $license->adminuser ? route('users.show', $license->adminuser) : null,
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
            'remaining' => $available - ($floatingConfig ? 0 : $unreassignable),
            'percent_remaining' => $percentRemaining,
            'min_amt' => $license->min_amt ? (int) $license->min_amt : null,
            'expiry' => $expiry?->format('d/m/Y'),
            'expiration_formatted' => $fmtDate($license->expiration_date),
            'days' => $days,
            'status' => $status,
            'termination_formatted' => $fmtDate($license->termination_date),
            'purchase_date_formatted' => $fmtDate($license->purchase_date),
            'created_at_formatted' => $fmtDate($license->created_at),
            'updated_at_formatted' => $fmtDate($license->updated_at),
            'license_email' => $license->license_email,
            'license_name' => $license->license_name,
            'maintained' => $license->maintained == 1,
            'reassignable' => $license->reassignable == 1,
            'requestable' => (bool) $license->requestable,
            'order_number' => $license->order_number,
            'purchase_order' => $license->purchase_order,
            'notes_html' => Helper::parseEscapedMarkedownInline($license->notes),
            'unit_cost' => $license->purchase_cost !== null ? Helper::formatCurrencyOutput($license->purchase_cost) : null,
            'total_cost' => is_numeric($license->purchase_cost)
                ? Helper::formatCurrencyOutput(round(((float) $license->purchase_cost) * $seats, 2))
                : null,
            'per_user_cost' => $perUserCost,
            'cost_monthly' => $cost !== null ? $cost / 12 : null,
            'cost_yearly' => $cost,
            'currency' => $currency,
            'cost_monthly_base' => $cost !== null ? CurrencyConverter::toBase($cost / 12, $currency ?? CurrencyConverter::base()) : null,
            'cost_yearly_base' => $cost !== null ? CurrencyConverter::toBase($cost, $currency ?? CurrencyConverter::base()) : null,
        ];
    }
}
