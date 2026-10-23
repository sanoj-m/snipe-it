<?php

namespace Tests\Feature\Settings;

use App\Models\License;
use App\Models\Setting;
use App\Models\User;
use App\Services\Killa\CurrencyConverter;
use Carbon\Carbon;
use Tests\TestCase;

class CurrencySettingsTest extends TestCase
{
    public function test_non_superuser_cannot_view_currency_settings(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('settings.currency.index'))
            ->assertForbidden();
    }

    public function test_superuser_can_save_exchange_rates(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('settings.currency.save'), [
                'rates' => [
                    ['code' => 'USD', 'rate' => 3.6725],
                    ['code' => 'eur', 'rate' => 4.0],
                ],
            ])
            ->assertRedirect(route('settings.currency.index'));

        $rates = CurrencyConverter::rates();
        $this->assertSame(3.6725, $rates['USD']);
        $this->assertSame(4.0, $rates['EUR']);
    }

    public function test_base_currency_row_is_not_stored(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('settings.currency.save'), [
                'rates' => [
                    ['code' => 'AED', 'rate' => 1],
                    ['code' => 'USD', 'rate' => 3.6725],
                ],
            ]);

        $this->assertArrayNotHasKey('AED', CurrencyConverter::rates());
    }

    public function test_rates_validation_rejects_bad_input(): void
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('settings.currency.save'), [
                'rates' => [['code' => 'USDD', 'rate' => -1]],
            ])
            ->assertSessionHasErrors();
    }

    public function test_to_base_converts_with_configured_rate(): void
    {
        Setting::getSettings()->update(['currency_exchange_rates' => json_encode(['USD' => 4.0])]);

        $this->assertSame(400.0, CurrencyConverter::toBase(100.0, 'usd'));
        $this->assertNull(CurrencyConverter::toBase(100.0, 'AED'));
        $this->assertNull(CurrencyConverter::toBase(100.0, 'ZZZ'));
    }

    public function test_license_accepts_currency_and_transformer_shows_conversion(): void
    {
        Setting::getSettings()->update(['currency_exchange_rates' => json_encode(['USD' => 4.0])]);
        $license = License::factory()->create([
            'name' => 'USDSoft',
            'currency' => 'USD',
            'purchase_cost' => 100,
            'seats' => 2,
            'expiration_date' => Carbon::today()->addYear(),
        ]);

        $row = (new \App\Http\Transformers\LicensesTransformer)->transformLicense($license);

        $this->assertSame('USD', $license->fresh()->currency);
        $this->assertStringContainsString('USD 200.00', $row['cost_yearly']);
        $this->assertStringContainsString('800.00', $row['cost_yearly']); // 100 * 2 seats * 4.0
    }
}
