@extends('layouts/default')

{{-- [license-spend addon] AI spend report: full AED of AI licenses + AI portions elsewhere --}}

@section('title')
    {{ trans('killa.license_spend.ai_title') }}
@parent
@stop

@section('content')
    <x-container>
        <div class="box box-default">
            <div class="box-header with-border">
                <h2 class="box-title">{{ trans('killa.license_spend.ai_title') }}</h2>
            </div>
            <div class="box-body">

                @include('killa/reports/license-spend/_nav')

                <form method="GET" action="{{ route('reports.license-spend.ai') }}" class="form-inline" style="margin-bottom: 15px;">
                    <input type="number" class="form-control" name="year" value="{{ $year }}" min="2000" max="2100"
                           placeholder="{{ trans('killa.license_spend.all_years') }}"
                           aria-label="{{ trans('killa.license_spend.year') }}" style="width: 120px;">
                    <button type="submit" class="btn btn-primary">{{ trans('general.search') }}</button>
                    <a href="{{ route('reports.license-spend.ai') }}" class="btn btn-link">{{ trans('killa.license_spend.clear') }}</a>
                </form>

                <div class="well well-sm" style="max-width: 360px;">
                    <strong style="font-size: 1.4em;">{{ number_format($total, 2) }}</strong>
                    <span class="text-muted">{{ trans('killa.license_spend.ai_total', ['base' => \App\Services\Killa\CurrencyConverter::base()]) }}</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>{{ trans('general.license') }}</th>
                            <th>{{ trans('general.purchase_date') }}</th>
                            <th>{{ trans('killa.license_spend.ai_kind') }}</th>
                            <th class="text-right">{{ trans('killa.license_spend.ai_amount_col', ['base' => \App\Services\Killa\CurrencyConverter::base()]) }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($rows as $purchase)
                            <tr>
                                <td><a href="{{ route('licenses.show', $purchase->license_id) }}">{{ $purchase->license?->name }}</a></td>
                                <td>{{ $purchase->purchase_date?->format('Y-m-d') }}</td>
                                <td>
                                    @if ($purchase->license?->is_ai)
                                        <span class="label label-info">{{ trans('killa.license_spend.ai_full') }}</span>
                                    @else
                                        <span class="label label-default">{{ trans('killa.license_spend.ai_portion') }}</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    @if ($purchase->license?->is_ai)
                                        {{ number_format(\App\Services\Killa\LicenseSpendCalculator::aedAmount($purchase) ?? 0, 2) }}
                                    @else
                                        {{ number_format((float) $purchase->ai_amount_aed, 2) }}
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">{{ trans('killa.license_spend.no_data') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </x-container>
@stop
