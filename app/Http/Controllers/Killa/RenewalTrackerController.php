<?php

namespace App\Http\Controllers\Killa;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\License;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->with('category', 'location')
            ->orderBy('name')
            ->get();

        $floatingIds = FloatingLicenseConfig::query()->pluck('license_id')->flip();

        $today = Carbon::today();
        $rows = $licenses->map(fn (License $license) => $this->row($license, $floatingIds->has($license->id), $today));

        $groups = $rows
            ->groupBy('category')
            ->sortKeys(SORT_NATURAL | SORT_FLAG_CASE)
            ->map(fn ($group) => $group->sortBy([
                // Manual drag order first (per category), unset rows fall through
                fn ($a, $b) => ($a['sort_order'] ?? PHP_INT_MAX) <=> ($b['sort_order'] ?? PHP_INT_MAX),
                // Urgency first: expired/soonest expiry at the top, perpetual last
                fn ($a, $b) => ($a['days'] ?? PHP_INT_MAX) <=> ($b['days'] ?? PHP_INT_MAX),
                'name',
            ])->values());

        return view('killa/renewals/index', ['groups' => $groups]);
    }

    public function reorder(Request $request): JsonResponse
    {
        if (! Gate::allows('update', License::class)) {
            abort(403);
        }

        $validated = $request->validate([
            'order' => 'required|array|min:1',
            'order.*' => 'integer|distinct',
        ]);

        $licenses = Company::scopeCompanyables(License::query())
            ->whereIn('id', $validated['order'])
            ->get()
            ->keyBy('id');

        foreach ($validated['order'] as $position => $id) {
            if ($license = $licenses->get($id)) {
                $license->sort_order = $position + 1;
                $license->save();
            }
        }

        return response()->json(['status' => 'success', 'message' => trans('killa.renewals.reorder_saved')]);
    }

    /**
     * @return array{id: int, sort_order: int|null, name: string, url: string, location: string|null, category: string, type: string,
     *               floating: bool, term: string|null, seats: int,
     *               expiry: string|null, days: int|null, status: string,
     *               cost_monthly: float|null, cost_yearly: float|null,
     *               currency: string|null, cost_monthly_aed: float|null,
     *               cost_yearly_aed: float|null, notes: string|null}
     */
    private function row(License $license, bool $floating, Carbon $today): array
    {
        $expiry = $license->perpetual ? null : $license->expiration_date;
        $days = $expiry ? (int) $today->diffInDays($expiry, false) : null;

        $term = null;
        $years = null;
        if ($license->purchase_date && $expiry) {
            $years = max(1, (int) round($license->purchase_date->diffInYears($expiry)));
            $term = $years === 1 ? trans('killa.renewals.term_yearly') : trans('killa.renewals.term_years', ['years' => $years]);
        }

        $status = 'none';
        if ($days !== null) {
            $status = $days <= 30 ? 'danger' : ($days <= 60 ? 'warning' : 'success');
        }

        // purchase_cost is per-seat (server convention); sheet costs are totals
        $cost = $license->purchase_cost !== null ? (float) $license->purchase_cost * max(1, (int) $license->seats) : null;
        $currency = $license->currency ? strtoupper($license->currency) : null;

        return [
            'id' => $license->id,
            'sort_order' => $license->sort_order !== null ? (int) $license->sort_order : null,
            'name' => $license->name,
            'url' => route('licenses.show', $license),
            'location' => $license->location?->name,
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
            'currency' => $currency,
            'cost_monthly_aed' => $cost !== null ? \App\Services\Killa\CurrencyConverter::toBase($cost / 12, $currency) : null,
            'cost_yearly_aed' => $cost !== null ? \App\Services\Killa\CurrencyConverter::toBase($cost, $currency) : null,
            'notes' => trim((string) $license->notes) !== ''
                ? $license->notes
                : $this->autoNotes($license, $years, $currency, $floating),
        ];
    }

    /**
     * Fallback notes derived from license data when no notes are stored.
     */
    private function autoNotes(License $license, ?int $years, ?string $currency, bool $floating): ?string
    {
        $parts = [];

        if ($license->perpetual) {
            $parts[] = trans('killa.renewals.note_perpetual');
        }
        if ($years !== null && $years > 1) {
            $parts[] = trans('killa.renewals.note_term', ['years' => $years]);
        }
        if ($currency !== null && $currency !== \App\Services\Killa\CurrencyConverter::base()) {
            $parts[] = trans('killa.renewals.note_cost_currency', ['currency' => $currency]);
        }
        if ($floating) {
            $parts[] = trans('killa.renewals.note_floating');
        }
        if ($parts === [] && ! $license->perpetual) {
            $parts[] = trans('killa.renewals.note_standard');
        }

        return $parts === [] ? null : implode(' · ', $parts);
    }
}
