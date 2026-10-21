@extends('layouts/default')

@section('title')
{{ trans('killa.renewals.title') }}
@parent
@stop

@section('header_right')
@stop

@section('content')
{{-- [killa-v2] renewal tracker: server-rendered grouped table. Scoped styles
     below use design tokens only (zero-build rule). --}}
<style>
    .k-renewals-table th, .k-renewals-table td { vertical-align: middle !important; }
    .k-renewals-group td {
        background-color: var(--k-table-head-bg) !important;
        color: var(--k-text-muted);
        font-weight: 600;
        letter-spacing: 0.02em;
    }
    .k-renewals-num { text-align: right !important; font-variant-numeric: tabular-nums; }
    .k-renewals-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-right: var(--k-space-2);
        vertical-align: baseline;
    }
    .k-renewals-dot-success { background-color: var(--k-success); }
    .k-renewals-dot-warning { background-color: var(--k-warning); }
    .k-renewals-dot-danger { background-color: var(--k-danger); }
    .k-renewals-expired { color: var(--k-danger); font-weight: 600; }
    .k-renewals-muted { color: var(--k-text-muted); }
</style>

<x-container>
    <header class="k-insights-header">
        <div>
            <h1>{{ trans('killa.renewals.title') }}</h1>
            <p>{{ trans('killa.renewals.description') }}</p>
        </div>
    </header>

    <div class="box box-default">
        <div class="box-body table-responsive">
            @if ($groups->isEmpty())
                <p class="k-renewals-muted" style="padding: var(--k-space-4); margin: 0;">{{ trans('killa.renewals.empty') }}</p>
            @else
                <table class="table table-striped k-renewals-table">
                    <thead>
                        <tr>
                            <th class="k-renewals-num" scope="col">#</th>
                            <th scope="col">{{ trans('killa.renewals.software') }}</th>
                            <th scope="col">{{ trans('killa.renewals.license_type') }}</th>
                            <th scope="col">{{ trans('killa.renewals.floating') }}</th>
                            <th scope="col">{{ trans('killa.renewals.term') }}</th>
                            <th class="k-renewals-num" scope="col">{{ trans('killa.renewals.seats') }}</th>
                            <th scope="col">{{ trans('killa.renewals.expiry_date') }}</th>
                            <th class="k-renewals-num" scope="col">{{ trans('killa.renewals.days_to_expire') }}</th>
                            <th class="k-renewals-num" scope="col">{{ trans('killa.renewals.cost_monthly') }}</th>
                            <th class="k-renewals-num" scope="col">{{ trans('killa.renewals.cost_yearly') }}</th>
                            <th scope="col">{{ trans('killa.renewals.notes') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php($i = 0)
                        @foreach ($groups as $category => $rows)
                            <tr class="k-renewals-group">
                                <td colspan="11">{{ $category }}</td>
                            </tr>
                            @foreach ($rows as $row)
                                <tr>
                                    <td class="k-renewals-num k-renewals-muted">{{ ++$i }}</td>
                                    <td @class(['k-renewals-expired' => $row['status'] === 'danger' && $row['days'] !== null && $row['days'] < 0])>
                                        <a href="{{ $row['url'] }}">{{ $row['name'] }}</a>
                                    </td>
                                    <td>{{ $row['type'] }}</td>
                                    <td>{{ $row['floating'] ? trans('killa.renewals.floating_yes') : trans('killa.renewals.floating_no') }}</td>
                                    <td>{{ $row['term'] ?? '—' }}</td>
                                    <td class="k-renewals-num">{{ number_format($row['seats']) }}</td>
                                    <td>
                                        @if ($row['status'] !== 'none')
                                            <span class="k-renewals-dot k-renewals-dot-{{ $row['status'] }}" aria-hidden="true"></span>
                                        @endif
                                        {{ $row['expiry'] ?? trans('killa.renewals.no_expiry') }}
                                    </td>
                                    <td class="k-renewals-num">
                                        @if ($row['days'] === null)
                                            {{ trans('killa.renewals.no_expiry') }}
                                        @elseif ($row['days'] < 0)
                                            <span class="k-renewals-expired">{{ trans('killa.renewals.expired') }}</span>
                                        @else
                                            {{ trans('killa.renewals.days', ['days' => number_format($row['days'])]) }}
                                        @endif
                                    </td>
                                    <td class="k-renewals-num">
                                        @if ($row['cost_monthly'] !== null)
                                            {{ ($row['currency'] ?? $snipeSettings->default_currency).' '.number_format($row['cost_monthly'], 2) }}
                                            @if ($row['cost_monthly_aed'] !== null)
                                                <br><small class="k-renewals-muted">{{ trans('killa.currency.aed_equivalent', ['base' => $snipeSettings->default_currency, 'amount' => number_format($row['cost_monthly_aed'], 2)]) }}</small>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="k-renewals-num">
                                        @if ($row['cost_yearly'] !== null)
                                            {{ ($row['currency'] ?? $snipeSettings->default_currency).' '.number_format($row['cost_yearly'], 2) }}
                                            @if ($row['cost_yearly_aed'] !== null)
                                                <br><small class="k-renewals-muted">{{ trans('killa.currency.aed_equivalent', ['base' => $snipeSettings->default_currency, 'amount' => number_format($row['cost_yearly_aed'], 2)]) }}</small>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="k-renewals-muted">{{ $row['notes'] }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-container>
@stop
