{{-- [license-spend addon] Purchases / Renewals section for the license view
     page. Included by both upstream licenses/view.blade.php (fenced) and
     killa/licenses/view.blade.php. Expects $license with `purchases` loaded. --}}
@php
    $kspPurchases = $license->purchases;
    $kspBase = \App\Services\Killa\CurrencyConverter::base();
    $kspChartRows = $kspPurchases->filter(fn ($p) => \App\Services\Killa\LicenseSpendCalculator::aedAmount($p) !== null)->values();
    $kspTermLabel = function ($months) {
        if (! $months) {
            return '—';
        }
        $key = \App\Models\LicensePurchase::TERM_OPTIONS[$months] ?? null;

        return $key ? trans('killa.license_spend.terms.'.$key) : trans('killa.license_spend.terms.months', ['months' => $months]);
    };
@endphp

<div class="clearfix" style="margin-bottom: 10px;">
    @can('update', $license)
        <a href="{{ route('licenses.purchases.create', $license->id) }}" class="btn btn-primary btn-sm pull-right hidden-print">
            <x-icon type="plus" /> {{ trans('killa.license_spend.add_purchase') }}
        </a>
    @endcan
</div>

@if ($kspPurchases->isEmpty())
    <p class="text-muted">{{ trans('killa.license_spend.no_purchases') }}</p>
@else
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
            <tr>
                <th>{{ trans('general.purchase_date') }}</th>
                <th>{{ trans('killa.license_spend.period') }}</th>
                <th>{{ trans('killa.license_spend.term') }}</th>
                <th class="text-right">{{ trans('killa.license_spend.amount') }}</th>
                <th class="text-right">{{ $kspBase }}</th>
                <th class="text-right">{{ trans('killa.license_spend.ai_amount_aed') }}</th>
                <th>{{ trans('killa.license_spend.invoice') }}</th>
                <th>{{ trans('general.notes') }}</th>
                @can('update', $license)
                    <th class="hidden-print">{{ trans('table.actions') }}</th>
                @endcan
            </tr>
            </thead>
            <tbody>
            @foreach ($kspPurchases->sortByDesc('purchase_date') as $purchase)
                <tr>
                    <td>{{ $purchase->purchase_date?->format('Y-m-d') }}</td>
                    <td>
                        @if ($purchase->period_start || $purchase->period_end)
                            {{ $purchase->period_start?->format('Y-m-d') }} → {{ $purchase->period_end?->format('Y-m-d') }}
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $kspTermLabel($purchase->term_months) }}</td>
                    <td class="text-right">
                        @if ($purchase->amount !== null)
                            {{ strtoupper((string) $purchase->currency) }} {{ number_format((float) $purchase->amount, 2) }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-right">
                        @php $kspAed = \App\Services\Killa\LicenseSpendCalculator::aedAmount($purchase); @endphp
                        {{ $kspAed !== null ? number_format($kspAed, 2) : '—' }}
                    </td>
                    <td class="text-right">
                        {{ $purchase->ai_amount_aed !== null ? number_format((float) $purchase->ai_amount_aed, 2) : '—' }}
                    </td>
                    <td>
                        @if ($purchase->hasInvoice())
                            <a href="{{ route('licenses.purchases.invoice', [$license->id, $purchase->id]) }}">
                                <x-icon type="download" /> {{ trans('killa.license_spend.invoice') }}
                            </a>
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $purchase->notes }}</td>
                    @can('update', $license)
                        <td class="hidden-print">
                            <a href="{{ route('licenses.purchases.edit', [$license->id, $purchase->id]) }}"
                               class="btn btn-warning btn-sm" data-tooltip="true" title="{{ trans('general.edit') }}">
                                <x-icon type="edit" />
                            </a>
                            <form method="POST" action="{{ route('licenses.purchases.destroy', [$license->id, $purchase->id]) }}"
                                  style="display:inline" onsubmit="return confirm('{{ trans('general.sure_to_delete') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" data-tooltip="true" title="{{ trans('general.delete') }}">
                                    <x-icon type="delete" />
                                </button>
                            </form>
                        </td>
                    @endcan
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    @if ($kspChartRows->count() >= 2)
        <div class="chart-responsive" style="position: relative; height: 220px; margin-top: 15px;">
            <canvas id="licensePriceTrendChart" role="img"
                    aria-label="{{ trans('killa.license_spend.price_trend_chart') }}"></canvas>
        </div>
    @endif
@endif

@if ($kspChartRows->count() >= 2)
    @push('js')
        <script src="{{ url(mix('js/dist/Chart.min.js')) }}"></script>
        <script nonce="{{ csrf_token() }}">
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof window.Chart === 'undefined') {
                    return;
                }
                var trend = @json([
                    'labels' => $kspChartRows->map(fn ($p) => $p->purchase_date?->format('Y-m-d'))->values(),
                    'values' => $kspChartRows->map(fn ($p) => \App\Services\Killa\LicenseSpendCalculator::aedAmount($p))->values(),
                ]);
                var canvas = document.getElementById('licensePriceTrendChart');
                if (! canvas) {
                    return;
                }
                new Chart(canvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: trend.labels,
                        datasets: [{
                            label: '{{ $kspBase }}',
                            data: trend.values,
                            borderColor: '{{ \App\Helpers\Helper::defaultChartColors(0) }}',
                            pointBackgroundColor: '{{ \App\Helpers\Helper::defaultChartColors(0) }}',
                            borderWidth: 2,
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            lineTension: 0.25,
                            fill: false,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        legend: {display: false},
                        scales: {
                            xAxes: [{gridLines: {display: false}}],
                            yAxes: [{ticks: {beginAtZero: true}}],
                        },
                    },
                });
            });
        </script>
    @endpush
@endif
