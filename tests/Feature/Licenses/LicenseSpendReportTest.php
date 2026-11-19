<?php

namespace Tests\Feature\Licenses;

use App\Models\License;
use App\Models\LicensePurchase;
use App\Models\Setting;
use App\Models\User;
use App\Services\Killa\LicenseSpendCalculator;
use Carbon\Carbon;
use Tests\TestCase;

class LicenseSpendReportTest extends TestCase
{
    private function purchase(License $license, array $attributes = []): LicensePurchase
    {
        return LicensePurchase::create(array_merge([
            'license_id' => $license->id,
            'purchase_date' => '2026-03-10',
            'amount_aed' => 1200,
            'invoice_file' => 'private_uploads/licenses/x.pdf',
        ], $attributes));
    }

    public function test_report_pages_render()
    {
        $user = User::factory()->superuser()->create();

        foreach (['reports.license-spend', 'reports.license-spend.forecast', 'reports.license-spend.ai', 'reports.license-spend.attention'] as $route) {
            $this->actingAs($user)->get(route($route))->assertOk();
        }
    }

    public function test_report_pages_require_permission()
    {
        $user = User::factory()->create();

        foreach (['reports.license-spend', 'reports.license-spend.forecast', 'reports.license-spend.ai', 'reports.license-spend.attention'] as $route) {
            $this->actingAs($user)->get(route($route))->assertForbidden();
        }
    }

    public function test_csv_export_downloads()
    {
        $license = License::factory()->create();
        $this->purchase($license);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('reports.license-spend.export'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv');
    }

    public function test_yearly_spend_is_cash_basis()
    {
        $license = License::factory()->create();
        $this->purchase($license, ['purchase_date' => '2026-03-10', 'amount_aed' => 500]);
        $this->purchase($license, ['purchase_date' => '2025-03-10', 'amount_aed' => 300]);

        $this->assertEquals(500, LicenseSpendCalculator::yearlySpend($license->purchases, 2026));
        $this->assertEquals(300, LicenseSpendCalculator::yearlySpend($license->purchases, 2025));
    }

    public function test_monthly_breakdown_cash_vs_amortized()
    {
        $license = License::factory()->create();
        $this->purchase($license, [
            'purchase_date' => '2026-01-15',
            'period_start' => '2026-01-15',
            'period_end' => '2027-01-14',
            'term_months' => 12,
            'amount_aed' => 1200,
        ]);

        $cash = LicenseSpendCalculator::monthlyBreakdown($license->purchases, 2026, false);
        $this->assertEquals(1200, $cash[1]);
        $this->assertEquals(0, $cash[2]);

        $amortized = LicenseSpendCalculator::monthlyBreakdown($license->purchases, 2026, true);
        // 2026-01-15 → 2027-01-14 is 11 whole months of diffInMonths; the
        // amortizer spreads over coverageMonths() = 11, one month per step.
        $span = $license->purchases->first()->coverageMonths();
        foreach (range(1, $span) as $month) {
            $this->assertEquals(round(1200 / $span, 2), $amortized[$month]);
        }
        $this->assertEquals(0, $amortized[12]);
    }

    public function test_aed_amount_falls_back_to_exchange_rate()
    {
        $settings = Setting::getSettings();
        $settings->default_currency = 'AED';
        $settings->currency_exchange_rates = json_encode(['USD' => 2.0]);
        $settings->save();

        $license = License::factory()->create();
        $purchase = $this->purchase($license, ['amount_aed' => null, 'amount' => 100, 'currency' => 'USD']);

        $this->assertEquals(200, LicenseSpendCalculator::aedAmount($purchase));

        $base = $this->purchase($license, ['amount_aed' => null, 'amount' => 100, 'currency' => 'AED']);
        $this->assertEquals(100, LicenseSpendCalculator::aedAmount($base));
    }

    public function test_forecast_distinguishes_renewed_from_carried_price()
    {
        $year = (int) date('Y') + 1;

        $renewed = License::factory()->create(['is_subscription' => true]);
        $this->purchase($renewed, [
            'term_months' => 12,
            'period_start' => Carbon::today()->toDateString(),
            'period_end' => Carbon::create($year, 6, 1)->toDateString(),
            'amount_aed' => 1200,
        ]);

        $lapsed = License::factory()->create(['is_subscription' => true]);
        $this->purchase($lapsed, [
            'term_months' => 12,
            'period_start' => '2020-01-01',
            'period_end' => '2021-01-01',
            'amount_aed' => 2400,
        ]);

        $rows = LicenseSpendCalculator::forecast(
            License::with('purchases')->where('is_subscription', true)->get(),
            $year
        )->keyBy(fn ($row) => $row['license']->id);

        $this->assertEquals('renewed', $rows[$renewed->id]['status']);
        $this->assertEquals('forecast', $rows[$lapsed->id]['status']);
        $this->assertEquals(2400, $rows[$lapsed->id]['annual_aed']);
    }

    public function test_attention_items_flags_each_condition()
    {
        Carbon::setTestNow('2026-06-15');

        $noPurchases = License::factory()->create(['is_subscription' => true]);

        $missingInvoice = License::factory()->create();
        $this->purchase($missingInvoice, ['invoice_file' => null]);

        $missingAed = License::factory()->create();
        $this->purchase($missingAed, ['amount_aed' => null, 'amount' => null, 'currency' => null]);

        $lapsedSub = License::factory()->create(['is_subscription' => true]);
        $this->purchase($lapsedSub, ['period_start' => '2025-01-01', 'period_end' => '2026-01-01']);

        $perpetualNoCost = License::factory()->create(['perpetual' => true, 'purchase_cost' => null]);

        $clean = License::factory()->create();
        $this->purchase($clean);

        $items = LicenseSpendCalculator::attentionItems(
            License::with('purchases')->get()
        )->keyBy(fn ($item) => $item['license']->id);

        $this->assertContains('no_purchases', $items[$noPurchases->id]['reasons']);
        $this->assertContains('missing_invoice', $items[$missingInvoice->id]['reasons']);
        $this->assertContains('missing_amount_aed', $items[$missingAed->id]['reasons']);
        $this->assertContains('lapsed', $items[$lapsedSub->id]['reasons']);
        $this->assertContains('perpetual_no_cost', $items[$perpetualNoCost->id]['reasons']);
        $this->assertArrayNotHasKey($clean->id, $items);

        Carbon::setTestNow();
    }

    public function test_ai_spend_counts_full_ai_licenses_plus_portions()
    {
        $aiLicense = License::factory()->create(['is_ai' => true]);
        $this->purchase($aiLicense, ['purchase_date' => '2026-03-10', 'amount_aed' => 1000]);

        $mixed = License::factory()->create(['is_ai' => false]);
        $this->purchase($mixed, ['purchase_date' => '2026-03-10', 'amount_aed' => 800, 'ai_amount_aed' => 200]);

        $purchases = LicensePurchase::with('license')->get();

        $this->assertEquals(1200, LicenseSpendCalculator::aiSpend($purchases, 2026));
        $this->assertEquals(0, LicenseSpendCalculator::aiSpend($purchases, 2025));
    }

    public function test_dashboard_renders_with_license_spend_widget()
    {
        $license = License::factory()->create(['is_subscription' => true]);
        $this->purchase($license);

        $this->actingAs(User::factory()->superuser()->create())
            ->get('/')
            ->assertOk()
            ->assertSee(trans('killa.license_spend.widget_title'));
    }
}
