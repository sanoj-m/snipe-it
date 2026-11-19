<?php

namespace App\Services\Killa;

use App\Models\License;
use App\Models\LicensePurchase;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

/**
 * [license-spend addon] All AED spend math for license purchases/renewals:
 * yearly + monthly (amortized vs cash) breakdowns, renewal forecasts,
 * attention-needed flags, and the dashboard widget aggregate. Pure functions
 * over License/LicensePurchase collections — query building lives in the
 * callers (controllers).
 */
class LicenseSpendCalculator
{
    /**
     * The AED value of a purchase: the recorded amount_aed, falling back to
     * converting `amount` through the configured exchange rates. Null when
     * no AED value can be determined.
     */
    public static function aedAmount(LicensePurchase $purchase): ?float
    {
        if ($purchase->amount_aed !== null) {
            return (float) $purchase->amount_aed;
        }

        if ($purchase->amount === null) {
            return null;
        }

        $base = CurrencyConverter::base();
        $currency = strtoupper((string) $purchase->currency);

        if ($currency === '' || $currency === $base) {
            return (float) $purchase->amount;
        }

        return CurrencyConverter::toBase((float) $purchase->amount, $currency);
    }

    /**
     * Total AED spent in a calendar year, cash basis (purchase_date in year).
     *
     * @param  iterable<LicensePurchase>  $purchases
     */
    public static function yearlySpend(iterable $purchases, int $year): float
    {
        $total = 0.0;
        foreach ($purchases as $purchase) {
            if ($purchase->purchase_date && (int) $purchase->purchase_date->format('Y') === $year) {
                $total += static::aedAmount($purchase) ?? 0.0;
            }
        }

        return round($total, 2);
    }

    /**
     * 12 monthly AED totals for a year.
     * Amortized: each purchase's AED spread evenly over its coverage months.
     * Cash basis: full AED in the purchase month.
     *
     * @param  iterable<LicensePurchase>  $purchases
     * @return array<int, float> month number (1-12) => amount
     */
    public static function monthlyBreakdown(iterable $purchases, int $year, bool $amortized): array
    {
        $months = array_fill_keys(range(1, 12), 0.0);

        foreach ($purchases as $purchase) {
            $aed = static::aedAmount($purchase);
            if ($aed === null) {
                continue;
            }

            if (! $amortized) {
                if ($purchase->purchase_date && (int) $purchase->purchase_date->format('Y') === $year) {
                    $months[(int) $purchase->purchase_date->format('n')] += $aed;
                }

                continue;
            }

            $start = ($purchase->period_start ?? $purchase->purchase_date)?->copy()->startOfMonth();
            if (! $start) {
                continue;
            }
            $span = $purchase->coverageMonths();
            $perMonth = $aed / $span;

            for ($i = 0; $i < $span; $i++) {
                $month = $start->copy()->addMonths($i);
                if ((int) $month->format('Y') === $year) {
                    $months[(int) $month->format('n')] += $perMonth;
                }
            }
        }

        return array_map(fn (float $v) => round($v, 2), $months);
    }

    /**
     * Forecast subscription spend for a calendar year. For each subscription
     * license the latest purchase decides: when its coverage reaches into the
     * target year the price is "renewed" (actual), otherwise it is "forecast"
     * (price carried from the last purchase). Prices are annualized.
     *
     * @param  SupportCollection<int, License>  $subscriptions  licenses with purchases eager-loaded
     * @return SupportCollection<int, array{license: License, purchase: LicensePurchase|null, status: string, annual_aed: float|null}>
     */
    public static function forecast(SupportCollection $subscriptions, int $year): SupportCollection
    {
        $yearStart = Carbon::create($year, 1, 1);

        return $subscriptions->map(function (License $license) use ($yearStart) {
            $purchase = $license->purchases->sortByDesc('purchase_date')->first();

            if (! $purchase) {
                return [
                    'license' => $license,
                    'purchase' => null,
                    'status' => 'none',
                    'annual_aed' => null,
                ];
            }

            $aed = static::aedAmount($purchase);
            $annual = $aed !== null ? round($aed / $purchase->coverageMonths() * 12, 2) : null;
            $renewed = $purchase->period_end && $purchase->period_end->gte($yearStart);

            return [
                'license' => $license,
                'purchase' => $purchase,
                'status' => $renewed ? 'renewed' : 'forecast',
                'annual_aed' => $annual,
            ];
        })->values();
    }

    /**
     * Licenses whose subscription renewal falls due within the next $days.
     * Due date = latest purchase's period_end.
     *
     * @param  SupportCollection<int, License>  $subscriptions
     * @return SupportCollection<int, array{license: License, due: Carbon, purchase: LicensePurchase}>
     */
    public static function upcomingRenewals(SupportCollection $subscriptions, int $days = 90): SupportCollection
    {
        $today = Carbon::today();
        $limit = $today->copy()->addDays($days);

        return $subscriptions->map(function (License $license) {
            $purchase = $license->purchases->sortByDesc('period_end')->first();

            if (! $purchase || ! $purchase->period_end) {
                return null;
            }

            $due = $purchase->period_end->copy()->startOfDay();

            return $due->gte($today) && $due->lte($limit)
                ? ['license' => $license, 'due' => $due, 'purchase' => $purchase]
                : null;
        })->filter()->sortBy('due')->values();
    }

    /**
     * Data-quality / renewal-risk flags, one row per flagged license.
     *
     * @param  SupportCollection<int, License>  $licenses  licenses with purchases eager-loaded
     * @return SupportCollection<int, array{license: License, reasons: list<string>}>
     */
    public static function attentionItems(SupportCollection $licenses): SupportCollection
    {
        $today = Carbon::today();

        return $licenses->map(function (License $license) use ($today) {
            $reasons = [];

            if ($license->is_subscription && $license->purchases->isEmpty()) {
                $reasons[] = 'no_purchases';
            }

            foreach ($license->purchases as $purchase) {
                if (! $purchase->hasInvoice()) {
                    $reasons[] = 'missing_invoice';
                    break;
                }
            }

            foreach ($license->purchases as $purchase) {
                if ($purchase->amount_aed === null) {
                    $reasons[] = 'missing_amount_aed';
                    break;
                }
            }

            if ($license->is_subscription && $license->purchases->isNotEmpty()) {
                $latestEnd = $license->purchases->max('period_end');
                if ($latestEnd && Carbon::parse($latestEnd)->startOfDay()->lt($today)) {
                    $reasons[] = 'lapsed';
                }
            }

            if ($license->perpetual && ($license->purchase_cost === null || (float) $license->purchase_cost <= 0)) {
                $reasons[] = 'perpetual_no_cost';
            }

            return $reasons ? ['license' => $license, 'reasons' => $reasons] : null;
        })->filter()->values();
    }

    /**
     * AI spend in a calendar year: full AED of purchases on licenses flagged
     * is_ai, plus the ai_amount_aed portions of every other purchase.
     *
     * @param  iterable<LicensePurchase>  $purchases  with license relation loaded
     */
    public static function aiSpend(iterable $purchases, ?int $year = null): float
    {
        $total = 0.0;
        foreach ($purchases as $purchase) {
            if ($year !== null && ((int) $purchase->purchase_date?->format('Y')) !== $year) {
                continue;
            }
            if ($purchase->license?->is_ai) {
                $total += static::aedAmount($purchase) ?? 0.0;
            } else {
                $total += (float) ($purchase->ai_amount_aed ?? 0);
            }
        }

        return round($total, 2);
    }

    /**
     * Aggregate for the dashboard "Licenses & Subscriptions" widget.
     *
     * @param  SupportCollection<int, License>  $licenses  all licenses, purchases eager-loaded
     */
    public static function dashboardStats(SupportCollection $licenses): array
    {
        $subscriptions = $licenses->where('is_subscription', true)->values();
        $purchases = $licenses->pluck('purchases')->flatten();
        $year = (int) Carbon::today()->format('Y');

        $projected = 0.0;
        foreach (static::forecast($subscriptions, $year) as $row) {
            $projected += $row['annual_aed'] ?? 0.0;
        }

        return [
            'total_licenses' => $licenses->count(),
            'total_tools' => $subscriptions->count(),
            'ai_count' => $licenses->where('is_ai', true)->count(),
            'spent_this_year' => static::yearlySpend($purchases, $year),
            'subscription_spend_total' => round($subscriptions->pluck('purchases')->flatten()
                ->sum(fn (LicensePurchase $p) => static::aedAmount($p) ?? 0.0), 2),
            'projected_12m' => round($projected, 2),
            'upcoming' => static::upcomingRenewals($subscriptions),
            'attention_count' => static::attentionItems($licenses)->count(),
        ];
    }
}
