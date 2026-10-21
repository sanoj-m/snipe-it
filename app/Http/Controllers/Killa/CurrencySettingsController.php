<?php

namespace App\Http\Controllers\Killa;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Killa\CurrencyConverter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencySettingsController extends Controller
{
    public function edit(): View
    {
        return view('killa/settings/currency', [
            'base' => CurrencyConverter::base(),
            'rates' => CurrencyConverter::rates(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'rates' => ['required', 'array'],
            'rates.*.code' => ['required', 'string', 'size:3', 'alpha'],
            'rates.*.rate' => ['required', 'numeric', 'gt:0'],
        ]);

        $rates = [];
        foreach ($validated['rates'] as $row) {
            $code = strtoupper($row['code']);
            if ($code !== CurrencyConverter::base()) {
                $rates[$code] = (float) $row['rate'];
            }
        }

        $setting = Setting::getSettings();
        $setting->currency_exchange_rates = json_encode($rates);
        $setting->save();

        return redirect()->route('settings.currency.index')
            ->with('success', trans('killa.currency.saved'));
    }

    public function fetch(): RedirectResponse
    {
        $base = CurrencyConverter::base();

        try {
            $response = Http::timeout(10)->get(CurrencyConverter::RATES_URL.$base);
        } catch (\Throwable $e) {
            Log::warning('Currency rate fetch failed: '.$e->getMessage());

            return redirect()->route('settings.currency.index')
                ->with('error', trans('killa.currency.fetch_failed'));
        }

        if (! $response->ok() || $response->json('result') !== 'success') {
            return redirect()->route('settings.currency.index')
                ->with('error', trans('killa.currency.fetch_failed'));
        }

        // Keep currencies already configured, plus a common shortlist.
        $keep = array_unique(array_merge(
            array_keys(CurrencyConverter::rates()),
            ['USD', 'EUR', 'GBP', 'ZAR', 'AUD', 'SAR', 'INR', 'CAD']
        ));

        $fetched = [];
        foreach ($keep as $code) {
            $rate = $response->json("rates.$code");
            // API returns "1 base = X currency"; we store "1 currency = X base"
            if (is_numeric($rate) && $rate > 0 && $code !== $base) {
                $fetched[$code] = round(1 / (float) $rate, 6);
            }
        }

        return redirect()->route('settings.currency.index')
            ->with('fetched_rates', $fetched)
            ->with('success', trans('killa.currency.fetch_ok'));
    }
}
