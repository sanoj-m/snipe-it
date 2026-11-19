{{-- [license-spend addon] "Licenses & Subscriptions" dashboard widget.
     Expects $licenseSpend from Killa\DashboardController@index. --}}
@php $kspBase = \App\Services\Killa\CurrencyConverter::base(); @endphp
<div class="col-md-8 col-sm-12">
    <div class="box box-default k-insights-panel">
        <div class="box-header with-border k-insights-panel-header">
            <div>
                <h2 class="box-title">{{ trans('killa.license_spend.widget_title') }}</h2>
                <p class="k-insights-panel-description">{{ trans('killa.license_spend.widget_description') }}</p>
            </div>
            <a href="{{ route('reports.license-spend.attention') }}" class="k-insights-period" data-tooltip="true"
               title="{{ trans('killa.license_spend.attention_title') }}">
                @if ($licenseSpend['attention_count'] > 0)
                    <span class="label label-danger">{{ trans('killa.license_spend.attention_badge', ['count' => number_format($licenseSpend['attention_count'])]) }}</span>
                @else
                    <span class="label label-success">{{ trans('killa.all_clear') }}</span>
                @endif
            </a>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-4 col-sm-6">
                    <ul class="list-unstyled">
                        <li><strong>{{ number_format($licenseSpend['total_licenses']) }}</strong> {{ trans('killa.license_spend.widget_total_licenses') }}</li>
                        <li><strong>{{ number_format($licenseSpend['total_tools']) }}</strong> {{ trans('killa.license_spend.widget_total_tools') }}</li>
                        <li><strong>{{ number_format($licenseSpend['ai_count']) }}</strong> {{ trans('killa.license_spend.widget_ai') }}</li>
                    </ul>
                </div>
                <div class="col-md-4 col-sm-6">
                    <ul class="list-unstyled">
                        <li>{{ trans('killa.license_spend.widget_spent_year', ['year' => date('Y')]) }} <strong>{{ $kspBase }} {{ number_format($licenseSpend['spent_this_year'], 2) }}</strong></li>
                        <li>{{ trans('killa.license_spend.widget_sub_total') }} <strong>{{ $kspBase }} {{ number_format($licenseSpend['subscription_spend_total'], 2) }}</strong></li>
                        <li>{{ trans('killa.license_spend.widget_projected') }} <strong>{{ $kspBase }} {{ number_format($licenseSpend['projected_12m'], 2) }}</strong></li>
                    </ul>
                </div>
                <div class="col-md-4 col-sm-12">
                    <strong>{{ trans('killa.license_spend.widget_upcoming') }}</strong>
                    @if ($licenseSpend['upcoming']->isEmpty())
                        <p class="text-muted">{{ trans('killa.license_spend.no_upcoming') }}</p>
                    @else
                        <ul class="list-unstyled" style="margin-top: 5px;">
                            @foreach ($licenseSpend['upcoming']->take(5) as $renewal)
                                <li>
                                    <a href="{{ route('licenses.show', $renewal['license']->id) }}">{{ $renewal['license']->name }}</a>
                                    <span class="text-muted">— {{ $renewal['due']->format('Y-m-d') }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <a href="{{ route('reports.license-spend.forecast') }}">{{ trans('killa.license_spend.view_forecast') }}</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
