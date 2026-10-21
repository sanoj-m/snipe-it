<?php

namespace App\Services\Killa;

use App\Models\Setting;

/**
 * Per-license currency support: exchange rates to the app's default currency,
 * stored as JSON in settings.currency_exchange_rates (Admin > Settings >
 * Currency). Rates mean "1 unit of currency = rate units of default currency".
 */
class CurrencyConverter
{
    public const RATES_URL = 'https://open.er-api.com/v6/latest/';

    /** @return array<string, float> */
    public static function rates(): array
    {
        $raw = Setting::getSettings()->currency_exchange_rates ?? null;

        if (! $raw) {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    public static function base(): string
    {
        return strtoupper(trim((string) (Setting::getSettings()->default_currency ?? 'AED')));
    }

    /**
     * Convert an amount from its own currency into the default currency.
     * Returns null when no rate is known (or the currency is the base).
     */
    public static function toBase(float $amount, ?string $currency): ?float
    {
        $currency = strtoupper(trim((string) $currency));

        if ($currency === '' || $currency === static::base()) {
            return null;
        }

        $rate = static::rates()[$currency] ?? null;

        return $rate ? round($amount * $rate, 2) : null;
    }

    /**
     * Currency codes selectable on the license form: the base currency plus
     * every currency with a configured rate.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $codes = array_merge([static::base()], array_keys(static::rates()));
        $codes = array_unique($codes);
        sort($codes);

        return array_combine($codes, $codes);
    }
}
