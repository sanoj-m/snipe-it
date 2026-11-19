@extends('layouts/default')

{{-- [license-spend addon] License Spend report: yearly totals per license +
     monthly breakdown (amortized vs cash basis) + CSV export --}}

@section('title')
    {{ trans('killa.license_spend.report_title') }}
@parent
@stop

@section('content')
    <x-container>
        <div class="box box-default">
            <div class="box-header with-border">
                <h2 class="box-title">{{ trans('killa.license_spend.report_title') }}</h2>
                <div class="box-tools pull-right">
                    <a href="{{ route('reports.license-spend.export', request()->query()) }}" class="btn btn-default btn-sm">
                        <x-icon type="download" /> {{ trans('general.export') }}
                    </a>
                </div>
            </div>
            <div class="box-body">

                @include('killa/reports/license-spend/_nav')

                <form method="GET" action="{{ route('reports.license-spend') }}" class="form-inline" style="margin-bottom: 15px;">
                    <input type="number" class="form-control" name="year" value="{{ $year }}" min="2000" max="2100"
                           aria-label="{{ trans('killa.license_spend.year') }}" style="width: 100px;">
                    <input type="date" class="form-control" name="start_date" value="{{ $filters['start_date'] }}"
                           aria-label="{{ trans('killa.license_spend.start_date') }}">
                    <input type="date" class="form-control" name="end_date" value="{{ $filters['end_date'] }}"
                           aria-label="{{ trans('killa.license_spend.end_date') }}">
                    <select name="license_id" class="form-control" aria-label="{{ trans('general.license') }}">
                        <option value="">{{ trans('killa.license_spend.all_licenses') }}</option>
                        @foreach ($license_list as $id => $name)
                            <option value="{{ $id }}" @selected((string) $filters['license_id'] === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    <select name="category_id" class="form-control" aria-label="{{ trans('general.category') }}">
                        <option value="">{{ trans('killa.license_spend.all_categories') }}</option>
                        @foreach ($category_list as $id => $name)
                            <option value="{{ $id }}" @selected((string) $filters['category_id'] === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    <label class="checkbox-inline">
                        <input type="checkbox" name="ai_only" value="1" @checked($filters['ai_only'])> {{ trans('killa.license_spend.ai_only') }}
                    </label>
                    <label class="checkbox-inline">
                        <input type="checkbox" name="subscription_only" value="1" @checked($filters['subscription_only'])> {{ trans('killa.license_spend.subscription_only') }}
                    </label>
                    <input type="hidden" name="basis" value="{{ $amortized ? 'amortized' : 'cash' }}">
                    <button type="submit" class="btn btn-primary">{{ trans('general.search') }}</button>
                    <a href="{{ route('reports.license-spend') }}" class="btn btn-link">{{ trans('killa.license_spend.clear') }}</a>
                </form>

                <div class="row" style="margin-bottom: 15px;">
                    <div class="col-sm-4">
                        <div class="well well-sm text-center">
                            <strong style="font-size: 1.4em;">{{ number_format($yearTotal, 2) }}</strong>
                            <div class="text-muted">{{ trans('killa.license_spend.spent_in_year', ['year' => $year, 'base' => \App\Services\Killa\CurrencyConverter::base()]) }}</div>
                        </div>
                    </div>
                </div>

                <h3>{{ trans('killa.license_spend.yearly_heading', ['year' => $year]) }}</h3>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>{{ trans('general.license') }}</th>
                            <th>{{ trans('general.category') }}</th>
                            <th class="text-right">{{ trans('killa.license_spend.spent_year_col', ['year' => $year]) }}</th>
                            <th class="text-right">{{ trans('killa.license_spend.spent_total_col') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td><a href="{{ route('licenses.show', $row['license']->id) }}">{{ $row['license']->name }}</a></td>
                                <td>{{ $row['license']->category?->name }}</td>
                                <td class="text-right">{{ number_format($row['year_total'], 2) }}</td>
                                <td class="text-right">{{ number_format($row['all_time'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">{{ trans('killa.license_spend.no_data') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <h3>
                    {{ trans('killa.license_spend.monthly_heading', ['year' => $year]) }}
                    <span class="pull-right btn-group btn-group-sm">
                        <a href="{{ route('reports.license-spend', array_merge(request()->query(), ['basis' => 'amortized'])) }}"
                           class="btn btn-{{ $amortized ? 'primary' : 'default' }}">{{ trans('killa.license_spend.basis_amortized') }}</a>
                        <a href="{{ route('reports.license-spend', array_merge(request()->query(), ['basis' => 'cash'])) }}"
                           class="btn btn-{{ $amortized ? 'default' : 'primary' }}">{{ trans('killa.license_spend.basis_cash') }}</a>
                    </span>
                </h3>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            @for ($m = 1; $m <= 12; $m++)
                                <th class="text-right">{{ \Carbon\Carbon::create($year, $m, 1)->format('M') }}</th>
                            @endfor
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            @foreach ($monthly as $amount)
                                <td class="text-right">{{ $amount > 0 ? number_format($amount, 2) : '—' }}</td>
                            @endforeach
                        </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </x-container>
@stop
