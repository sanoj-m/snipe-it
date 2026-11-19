<?php

namespace App\Http\Controllers\Killa;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\License;
use App\Models\LicensePurchase;
use App\Services\Killa\LicenseSpendCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use League\Csv\EscapeFormula;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * [license-spend addon] License Spend reports: yearly/monthly AED spend
 * (amortized vs cash), renewal forecast, AI spend, and the attention-needed
 * list. Everything is computed from license_purchases via
 * LicenseSpendCalculator; views are Killa-owned.
 */
class LicenseSpendReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('reports.view');

        $amortized = $request->input('basis', 'amortized') === 'amortized';
        $year = (int) $request->input('year', date('Y'));

        $purchases = $this->filteredPurchases($request)->with('license.category')->get();

        $rows = $purchases->groupBy('license_id')->map(function ($group, $licenseId) use ($year) {
            $license = $group->first()->license;

            return [
                'license' => $license,
                'year_total' => LicenseSpendCalculator::yearlySpend($group, $year),
                'all_time' => round($group->sum(
                    fn (LicensePurchase $p) => LicenseSpendCalculator::aedAmount($p) ?? 0.0
                ), 2),
            ];
        })->sortByDesc('year_total')->values();

        return view('killa/reports/license-spend/index', [
            'rows' => $rows,
            'monthly' => LicenseSpendCalculator::monthlyBreakdown($purchases, $year, $amortized),
            'year' => $year,
            'amortized' => $amortized,
            'yearTotal' => LicenseSpendCalculator::yearlySpend($purchases, $year),
            'filters' => $this->filterValues($request),
        ] + $this->filterOptions());
    }

    public function forecast(Request $request): View
    {
        $this->authorize('reports.view');

        $year = (int) $request->input('year', ((int) date('Y')) + 1);
        $subscriptions = License::with('purchases', 'category')
            ->where('is_subscription', true)
            ->orderBy('name')
            ->get();

        $rows = LicenseSpendCalculator::forecast($subscriptions, $year);

        return view('killa/reports/license-spend/forecast', [
            'rows' => $rows,
            'year' => $year,
            'total' => round($rows->sum(fn ($row) => $row['annual_aed'] ?? 0.0), 2),
        ]);
    }

    public function ai(Request $request): View
    {
        $this->authorize('reports.view');

        $year = $request->filled('year') ? (int) $request->input('year') : null;
        $purchases = LicensePurchase::with('license.category')
            ->whereHas('license', fn (Builder $q) => $q->where('is_ai', true))
            ->orWhereNotNull('ai_amount_aed')
            ->orderBy('purchase_date')
            ->get();

        $rows = $purchases->filter(function (LicensePurchase $p) use ($year) {
            return $year === null || (int) $p->purchase_date?->format('Y') === $year;
        })->values();

        return view('killa/reports/license-spend/ai', [
            'rows' => $rows,
            'year' => $year,
            'total' => LicenseSpendCalculator::aiSpend($rows),
        ]);
    }

    public function attention(): View
    {
        $this->authorize('reports.view');

        $licenses = License::with('purchases', 'category')->orderBy('name')->get();

        return view('killa/reports/license-spend/attention', [
            'items' => LicenseSpendCalculator::attentionItems($licenses),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('reports.view');

        $purchases = $this->filteredPurchases($request)
            ->with('license.category')
            ->orderBy('purchase_date')
            ->get();

        $base = \App\Services\Killa\CurrencyConverter::base();

        return new StreamedResponse(function () use ($purchases, $base) {
            $handle = fopen('php://output', 'w');
            $formatter = new EscapeFormula('`');

            fputcsv($handle, [
                trans('general.license'),
                trans('general.category'),
                trans('general.purchase_date'),
                trans('killa.license_spend.period_start'),
                trans('killa.license_spend.period_end'),
                trans('killa.license_spend.term_months'),
                trans('killa.license_spend.amount'),
                trans('killa.currency.label'),
                $base,
                trans('killa.license_spend.ai_amount_aed'),
            ], escape: '\\');

            foreach ($purchases as $purchase) {
                $row = [
                    $purchase->license?->name,
                    $purchase->license?->category?->name,
                    $purchase->purchase_date?->format('Y-m-d'),
                    $purchase->period_start?->format('Y-m-d'),
                    $purchase->period_end?->format('Y-m-d'),
                    $purchase->term_months,
                    $purchase->amount,
                    $purchase->currency,
                    LicenseSpendCalculator::aedAmount($purchase),
                    $purchase->ai_amount_aed,
                ];

                if (config('app.escape_formulas') === false) {
                    fputcsv($handle, $row, escape: '\\');
                } else {
                    fputcsv($handle, $formatter->escapeRecord($row), escape: '\\');
                }
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="license-spend-report-'.date('Y-m-d-his').'.csv"',
        ]);
    }

    private function filteredPurchases(Request $request): Builder
    {
        return LicensePurchase::query()
            ->when($request->filled('start_date'), fn (Builder $q) => $q->whereDate('purchase_date', '>=', $request->input('start_date')))
            ->when($request->filled('end_date'), fn (Builder $q) => $q->whereDate('purchase_date', '<=', $request->input('end_date')))
            ->when($request->filled('license_id'), fn (Builder $q) => $q->where('license_id', $request->integer('license_id')))
            ->when(
                $request->filled('category_id') || $request->boolean('ai_only') || $request->boolean('subscription_only'),
                fn (Builder $q) => $q->whereHas('license', function (Builder $q) use ($request) {
                    $q->when($request->filled('category_id'), fn (Builder $q) => $q->where('category_id', $request->integer('category_id')))
                        ->when($request->boolean('ai_only'), fn (Builder $q) => $q->where('is_ai', true))
                        ->when($request->boolean('subscription_only'), fn (Builder $q) => $q->where('is_subscription', true));
                })
            );
    }

    private function filterValues(Request $request): array
    {
        return [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'license_id' => $request->input('license_id'),
            'category_id' => $request->input('category_id'),
            'ai_only' => $request->boolean('ai_only'),
            'subscription_only' => $request->boolean('subscription_only'),
        ];
    }

    private function filterOptions(): array
    {
        return [
            'license_list' => License::orderBy('name')->pluck('name', 'id'),
            'category_list' => Category::where('category_type', 'license')->orderBy('name')->pluck('name', 'id'),
        ];
    }
}
