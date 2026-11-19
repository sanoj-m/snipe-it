@extends('layouts/default')

{{-- [license-spend addon] Forecast report: expected subscription spend for a chosen year --}}

@section('title')
    {{ trans('killa.license_spend.forecast_title') }}
@parent
@stop

@section('content')
    <x-container>
        <div class="box box-default">
            <div class="box-header with-border">
                <h2 class="box-title">{{ trans('killa.license_spend.forecast_title') }}</h2>
            </div>
            <div class="box-body">

                @include('killa/reports/license-spend/_nav')

                <form method="GET" action="{{ route('reports.license-spend.forecast') }}" class="form-inline" style="margin-bottom: 15px;">
                    <input type="number" class="form-control" name="year" value="{{ $year }}" min="2000" max="2100"
                           aria-label="{{ trans('killa.license_spend.year') }}" style="width: 100px;">
                    <button type="submit" class="btn btn-primary">{{ trans('general.search') }}</button>
                </form>

                <div class="well well-sm" style="max-width: 360px;">
                    <strong style="font-size: 1.4em;">{{ number_format($total, 2) }}</strong>
                    <span class="text-muted">{{ trans('killa.license_spend.forecast_total', ['year' => $year, 'base' => \App\Services\Killa\CurrencyConverter::base()]) }}</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>{{ trans('general.license') }}</th>
                            <th>{{ trans('general.category') }}</th>
                            <th>{{ trans('killa.license_spend.last_purchase') }}</th>
                            <th>{{ trans('killa.license_spend.coverage_until') }}</th>
                            <th>{{ trans('killa.license_spend.status') }}</th>
                            <th class="text-right">{{ trans('killa.license_spend.annual_aed', ['base' => \App\Services\Killa\CurrencyConverter::base()]) }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td><a href="{{ route('licenses.show', $row['license']->id) }}">{{ $row['license']->name }}</a></td>
                                <td>{{ $row['license']->category?->name }}</td>
                                <td>{{ $row['purchase']?->purchase_date?->format('Y-m-d') ?? '—' }}</td>
                                <td>{{ $row['purchase']?->period_end?->format('Y-m-d') ?? '—' }}</td>
                                <td>
                                    @if ($row['status'] === 'renewed')
                                        <span class="label label-success">{{ trans('killa.license_spend.status_renewed') }}</span>
                                    @elseif ($row['status'] === 'forecast')
                                        <span class="label label-warning">{{ trans('killa.license_spend.status_forecast') }}</span>
                                    @else
                                        <span class="label label-default">{{ trans('killa.license_spend.status_none') }}</span>
                                    @endif
                                </td>
                                <td class="text-right">{{ $row['annual_aed'] !== null ? number_format($row['annual_aed'], 2) : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">{{ trans('killa.license_spend.no_data') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </x-container>
@stop
