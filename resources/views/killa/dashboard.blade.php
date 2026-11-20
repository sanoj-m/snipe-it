@extends('layouts/default')

@section('title')
{{ trans('killa.insights_title') }}
@parent
@stop

@section('content')
<x-container>
    <header class="k-insights-header">
        <p>{{ trans('killa.insights_description') }}</p>
    </header>

    @if ($snipeSettings->dashboard_message != '')
        <div class="row">
            <div class="col-md-12">
                <div class="box box-default">
                    <div class="box-body">
                        {!! Helper::parseEscapedMarkedown($snipeSettings->dashboard_message) !!}
                    </div>
                </div>
            </div>
        </div>
    @endif

    <section aria-labelledby="k-insights-inventory-title">
        <h2 id="k-insights-inventory-title" class="sr-only">{{ trans('killa.inventory_overview') }}</h2>
        <div class="row k-insights-metrics">
            @isset($metrics['asset'])
                <div class="col-lg-2 col-md-4 col-sm-4 col-xs-6">
                    <a href="{{ route('hardware.index') }}" class="k-insights-metric">
                        <span class="k-insights-metric-top">
                            <span class="k-insights-metric-label">{{ trans('general.assets') }}</span>
                            <span class="k-insights-metric-icon" aria-hidden="true"><x-icon type="assets" /></span>
                        </span>
                        <strong class="k-insights-metric-value">{{ number_format($metrics['asset']['total']) }}</strong>
                        <span class="k-insights-metric-meta">
                            {{ trans('general.deployed') }} <strong>{{ number_format($metrics['asset']['deployed']) }}</strong>
                            <span aria-hidden="true">·</span>
                            {{ trans('general.ready_to_deploy') }} <strong>{{ number_format($metrics['asset']['available']) }}</strong>
                        </span>
                    </a>
                </div>
            @endisset

            @isset($metrics['license'])
                <div class="col-lg-2 col-md-4 col-sm-4 col-xs-6">
                    <a href="{{ route('licenses.index') }}" class="k-insights-metric">
                        <span class="k-insights-metric-top">
                            <span class="k-insights-metric-label">{{ trans('general.licenses') }}</span>
                            <span class="k-insights-metric-icon" aria-hidden="true"><x-icon type="licenses" /></span>
                        </span>
                        <strong class="k-insights-metric-value">{{ number_format($metrics['license']['total']) }}</strong>
                        <span class="k-insights-metric-meta">
                            {{ trans('killa.meta_seats_free', ['count' => number_format($metrics['license']['free'])]) }}
                            <span aria-hidden="true">·</span>
                            {{ trans('killa.meta_expiring_soon', ['count' => number_format($metrics['license']['expiring'])]) }}
                        </span>
                    </a>
                </div>
            @endisset

            @isset($metrics['accessory'])
                <div class="col-lg-2 col-md-4 col-sm-4 col-xs-6">
                    <a href="{{ route('accessories.index') }}" class="k-insights-metric">
                        <span class="k-insights-metric-top">
                            <span class="k-insights-metric-label">{{ trans('general.accessories') }}</span>
                            <span class="k-insights-metric-icon" aria-hidden="true"><x-icon type="accessories" /></span>
                        </span>
                        <strong class="k-insights-metric-value">{{ number_format($metrics['accessory']['total']) }}</strong>
                        <span class="k-insights-metric-meta">{{ trans('killa.meta_units_remaining', ['count' => number_format($metrics['accessory']['remaining'])]) }}</span>
                    </a>
                </div>
            @endisset

            @isset($metrics['consumable'])
                <div class="col-lg-2 col-md-4 col-sm-4 col-xs-6">
                    <a href="{{ route('consumables.index') }}" class="k-insights-metric">
                        <span class="k-insights-metric-top">
                            <span class="k-insights-metric-label">{{ trans('general.consumables') }}</span>
                            <span class="k-insights-metric-icon" aria-hidden="true"><x-icon type="consumables" /></span>
                        </span>
                        <strong class="k-insights-metric-value">{{ number_format($metrics['consumable']['total']) }}</strong>
                        <span class="k-insights-metric-meta">{{ trans('killa.meta_low_stock', ['count' => number_format($metrics['consumable']['low'])]) }}</span>
                    </a>
                </div>
            @endisset

            @isset($metrics['component'])
                <div class="col-lg-2 col-md-4 col-sm-4 col-xs-6">
                    <a href="{{ route('components.index') }}" class="k-insights-metric">
                        <span class="k-insights-metric-top">
                            <span class="k-insights-metric-label">{{ trans('general.components') }}</span>
                            <span class="k-insights-metric-icon" aria-hidden="true"><x-icon type="components" /></span>
                        </span>
                        <strong class="k-insights-metric-value">{{ number_format($metrics['component']['total']) }}</strong>
                        <span class="k-insights-metric-meta">{{ trans('killa.meta_units_remaining', ['count' => number_format($metrics['component']['remaining'])]) }}</span>
                    </a>
                </div>
            @endisset

            @isset($metrics['user'])
                <div class="col-lg-2 col-md-4 col-sm-4 col-xs-6">
                    <a href="{{ route('users.index') }}" class="k-insights-metric">
                        <span class="k-insights-metric-top">
                            <span class="k-insights-metric-label">{{ trans('general.people') }}</span>
                            <span class="k-insights-metric-icon" aria-hidden="true"><x-icon type="users" /></span>
                        </span>
                        <strong class="k-insights-metric-value">{{ number_format($metrics['user']['total']) }}</strong>
                        <span class="k-insights-metric-meta">{{ trans('killa.meta_with_items', ['count' => number_format($metrics['user']['with_items'])]) }}</span>
                    </a>
                </div>
            @endisset
        </div>
    </section>

    <div class="row dashboard-row-eq k-insights-row">
        @can('canViewUsersAndCheckoutables')
            <div class="col-md-4 col-sm-6">
                <livewire:needs-attention />
            </div>
            <div class="col-md-4 col-sm-6">
                <x-dashboard.today-calendar />
            </div>
        @endcan

        @can('view', \App\Models\Asset::class)
            <div class="col-md-4 col-sm-6">
                <div class="box box-default k-insights-panel">
                    <div class="box-header with-border">
                        <h2 class="box-title">
                            {{ $snipeSettings->dash_chart_type == 'name' ? trans('general.assets_by_status') : trans('general.assets_by_status_type') }}
                        </h2>
                    </div>
                    <div class="box-body dashboard-chart-body">
                        <div id="statusChartWrap" class="chart-responsive k-insights-chart-wrap">
                            <canvas
                                id="statusPieChart"
                                role="img"
                                aria-label="{{ trans('killa.asset_status_chart') }}"
                                aria-describedby="statusChartSummary"
                            ></canvas>
                            <div class="k-insights-chart-center" aria-hidden="true" hidden>
                                <strong id="statusChartTotal">{{ number_format($metrics['asset']['total'] ?? 0) }}</strong>
                                <span>{{ trans('general.assets') }}</span>
                            </div>
                        </div>
                        <p id="statusChartSummary" class="sr-only"></p>
                        <div id="statusChartState" class="k-insights-chart-state" role="status" hidden></div>
                    </div>
                </div>
            </div>
        @endcan
    </div>

    {{-- [license-spend addon] Licenses & Subscriptions widget --}}
    @if ($licenseSpend)
        <div class="row dashboard-row-eq k-insights-row">
            @include('killa/dashboard/_license-spend')
        </div>
    @endif

    <div class="row dashboard-row-eq k-insights-row">
        @can('view', \App\Models\Asset::class)
            <div class="col-md-8 col-sm-12">
                <div class="box box-default k-insights-panel">
                    <div class="box-header with-border k-insights-panel-header">
                        <div>
                            <h2 class="box-title">{{ trans('killa.asset_movement') }}</h2>
                            <p class="k-insights-panel-description">{{ trans('killa.asset_movement_description') }}</p>
                        </div>
                        <span class="k-insights-period">{{ trans('killa.last_30_days') }}</span>
                    </div>
                    <div class="box-body">
                        <div id="movementTotals" class="k-insights-movement-totals">
                            <span><i class="k-insights-legend-dot is-checkout" aria-hidden="true"></i>{{ trans('general.checkouts') }} <strong>{{ number_format($assetMovement['checkout_total']) }}</strong></span>
                            <span><i class="k-insights-legend-dot is-checkin" aria-hidden="true"></i>{{ trans('general.checkins') }} <strong>{{ number_format($assetMovement['checkin_total']) }}</strong></span>
                        </div>
                        <div
                            id="movementChartWrap"
                            class="chart-responsive k-insights-chart-wrap"
                            @if ($assetMovement['checkout_total'] + $assetMovement['checkin_total'] === 0) hidden @endif
                        >
                            <canvas
                                id="assetMovementChart"
                                role="img"
                                aria-label="{{ trans('killa.asset_movement_chart') }}"
                                aria-describedby="movementTotals"
                            ></canvas>
                        </div>
                        <div
                            id="movementChartState"
                            class="k-insights-chart-state"
                            role="status"
                            @if ($assetMovement['checkout_total'] + $assetMovement['checkin_total'] > 0) hidden @endif
                        >{{ trans('killa.no_asset_movement') }}</div>
                    </div>
                </div>
            </div>
        @endcan

        @if (Gate::allows('view', \App\Models\Consumable::class)
             || Gate::allows('view', \App\Models\Accessory::class)
             || Gate::allows('view', \App\Models\Component::class)
             || Gate::allows('view', \App\Models\AssetModel::class)
             || Gate::allows('view', \App\Models\License::class))
            <div class="col-md-4 col-sm-12">
                <x-dashboard.low-stock />
            </div>
        @endif
    </div>

    @can('canViewUsersAndCheckoutables')
        <div class="row k-insights-row">
            <div class="col-md-12">
                <x-dashboard.recent-activity />
            </div>
        </div>
    @endcan

    {{-- [killa-v2] bottom section: locations/companies + categories share
         one tabbed panel instead of two stacked half-width boxes --}}
    @php
        $dashShowLocations = Gate::allows('canViewUsersAndCheckoutables');
        $dashShowCategories = Gate::allows('view', \App\Models\Asset::class)
            || Gate::allows('view', \App\Models\Accessory::class)
            || Gate::allows('view', \App\Models\Consumable::class)
            || Gate::allows('view', \App\Models\Component::class)
            || Gate::allows('view', \App\Models\License::class);
        $dashLocationsLabel = ($snipeSettings->scope_locations_fmcs != '1' && $snipeSettings->full_multiple_companies_support == '1')
            ? trans('general.companies') : trans('general.locations');
    @endphp
    @if ($dashShowLocations || $dashShowCategories)
        <div class="row dashboard-row-eq k-insights-row">
            <div class="col-md-12">
                <div class="box box-default k-insights-panel">
                    <div class="box-header with-border k-insights-panel-header">
                        <h2 class="box-title">{{ trans('killa.inventory_breakdown') }}</h2>
                        <ul class="nav nav-tabs k-insights-tabs" role="tablist">
                            @if ($dashShowLocations)
                                <li role="presentation" class="active">
                                    <a href="#dashTabLocations" aria-controls="dashTabLocations" role="tab" data-toggle="tab">{{ $dashLocationsLabel }}</a>
                                </li>
                            @endif
                            @if ($dashShowCategories)
                                <li role="presentation" @if (! $dashShowLocations) class="active" @endif>
                                    <a href="#dashTabCategories" aria-controls="dashTabCategories" role="tab" data-toggle="tab">{{ trans('general.categories') }}</a>
                                </li>
                            @endif
                        </ul>
                    </div>
                    <div class="box-body">
                        <div class="tab-content">
                            @if ($dashShowLocations)
                                <div class="tab-pane active" id="dashTabLocations">
                                    @if (($snipeSettings->scope_locations_fmcs != '1') && ($snipeSettings->full_multiple_companies_support == '1'))
                                        <x-dashboard.companies-summary :bare="true" />
                                    @else
                                        <x-dashboard.locations-summary :bare="true" />
                                    @endif
                                    <div class="text-center">
                                        <a href="{{ ($snipeSettings->scope_locations_fmcs != '1' && $snipeSettings->full_multiple_companies_support == '1') ? route('companies.index') : route('locations.index') }}" class="btn btn-theme btn-sm">{{ trans('general.viewall') }}</a>
                                    </div>
                                </div>
                            @endif
                            @if ($dashShowCategories)
                                <div class="tab-pane{{ $dashShowLocations ? '' : ' active' }}" id="dashTabCategories">
                                    <x-dashboard.categories-summary :bare="true" />
                                    <div class="text-center">
                                        <a href="{{ route('categories.index') }}" class="btn btn-theme btn-sm">{{ trans('general.viewall') }}</a>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if (Gate::allows('update', \App\Models\Consumable::class)
         || Gate::allows('update', \App\Models\Accessory::class)
         || Gate::allows('update', \App\Models\Component::class))
        <x-modals.adjust-quantity />
    @endif
</x-container>
@stop

@section('moar_scripts')
@include('partials.bootstrap-table', ['simple_view' => true, 'nopages' => true])
@can('canViewUsersAndCheckoutables')
    <script src="{{ url(mix('js/dist/snipeit-calendar.js')) }}" nonce="{{ csrf_token() }}"></script>
    <script nonce="{{ csrf_token() }}">
        document.addEventListener('DOMContentLoaded', function () {
            var upcomingDayFormat = function (arg) {
                var d = arg.date;
                var localYmd = d.year + '-' + String(d.month + 1).padStart(2, '0') + '-' + String(d.day).padStart(2, '0');
                var today = new Date();
                var todayYmd = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');
                var tomorrow = new Date(today.getTime() + 86400000);
                var tomorrowYmd = tomorrow.getFullYear() + '-' + String(tomorrow.getMonth() + 1).padStart(2, '0') + '-' + String(tomorrow.getDate()).padStart(2, '0');
                if (localYmd === todayYmd) return '{{ trans('general.calendar_today') }}';
                if (localYmd === tomorrowYmd) return '{{ trans('general.calendar_tomorrow') }}';
                return arg.text;
            };

            window.snipeitCalendar.init('dashboard-today-calendar', {
                events: '{{ route('api.calendar.events') }}',
                initialView: 'listUpcoming',
                views: {
                    listUpcoming: {
                        type: 'list',
                        duration: {days: 7},
                    },
                },
                listDayFormat: upcomingDayFormat,
                listDaySideFormat: {
                    month: 'long',
                    day: 'numeric',
                    year: 'numeric',
                },
                headerToolbar: false,
                direction: '{{ \App\Helpers\Helper::determineLanguageDirection() }}',
                locale: '{{ str_replace('_', '-', app()->getLocale()) }}',
                urlState: false,
                limit: 10,
                onFetchMeta: function (meta) {
                    var more = document.getElementById('dashboard-today-more');
                    var link = document.getElementById('dashboard-today-more-link');
                    if (!more || !link) return;
                    if (meta.truncated) {
                        var remaining = Math.max(0, meta.total - meta.returned);
                        link.textContent = '{{ trans('general.calendar_upcoming_more') }}'.replace(':count', String(remaining));
                        more.style.display = '';
                    } else {
                        more.style.display = 'none';
                    }
                },
            });
        });
    </script>
@endcan

    <script nonce="{{ csrf_token() }}">
        {{-- bootstrap-table inside a hidden tab mis-measures its width;
             resetView on first show fixes columns/scroll --}}
        document.addEventListener('DOMContentLoaded', function () {
            $('.k-insights-tabs a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                $($(e.target).attr('href')).find('.snipe-table').bootstrapTable('resetView');
            });
        });
    </script>
@stop

@can('view', \App\Models\Asset::class)
@push('js')
<script src="{{ url(mix('js/dist/Chart.min.js')) }}"></script>
<script nonce="{{ csrf_token() }}">
    document.addEventListener('DOMContentLoaded', function () {
        var assetMovement = @json($assetMovement);
        var cssVar = function (name) {
            return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        };
        var showChartState = function (wrapId, stateId, message) {
            var wrap = document.getElementById(wrapId);
            var state = document.getElementById(stateId);
            if (wrap) wrap.hidden = true;
            if (state) {
                state.textContent = message;
                state.hidden = false;
            }
        };
        var hasMovement = assetMovement && (assetMovement.checkout_total + assetMovement.checkin_total > 0);

        if (typeof window.Chart === 'undefined') {
            if (hasMovement) showChartState('movementChartWrap', 'movementChartState', '{{ trans('killa.chart_unavailable') }}');
            showChartState('statusChartWrap', 'statusChartState', '{{ trans('killa.chart_unavailable') }}');
            return;
        }

        Chart.defaults.global.defaultFontColor = cssVar('--k-text-muted');
        Chart.defaults.global.defaultFontFamily = cssVar('--k-font');

        var movementCanvas = document.getElementById('assetMovementChart');
        if (movementCanvas && hasMovement) {
            new Chart(movementCanvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: assetMovement.labels,
                    datasets: [
                        {
                            label: '{{ trans('general.checkouts') }}',
                            data: assetMovement.checkouts,
                            borderColor: cssVar('--k-accent'),
                            pointBackgroundColor: cssVar('--k-accent'),
                            borderWidth: 2,
                            pointRadius: 0,
                            pointHoverRadius: 4,
                            lineTension: 0.25,
                            fill: false,
                        },
                        {
                            label: '{{ trans('general.checkins') }}',
                            data: assetMovement.checkins,
                            // [killa-fix-ui] neutral second series — the
                            // accent belongs to checkouts alone
                            borderColor: cssVar('--k-text-muted'),
                            pointBackgroundColor: cssVar('--k-text-muted'),
                            borderWidth: 2,
                            pointRadius: 0,
                            pointHoverRadius: 4,
                            lineTension: 0.25,
                            fill: false,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {display: false},
                    hover: {mode: 'index', intersect: false},
                    tooltips: {mode: 'index', intersect: false},
                    scales: {
                        xAxes: [{
                            gridLines: {display: false},
                            ticks: {maxTicksLimit: 10, maxRotation: 0},
                        }],
                        yAxes: [{
                            gridLines: {color: cssVar('--k-border-soft'), zeroLineColor: cssVar('--k-border')},
                            ticks: {beginAtZero: true, precision: 0},
                        }],
                    },
                },
            });
        }

        var statusCanvas = document.getElementById('statusPieChart');
        if (!statusCanvas) return;

        $.ajax({
            type: 'GET',
            url: '{{ $snipeSettings->dash_chart_type == 'name' ? route('api.statuslabels.assets.byname') : route('api.statuslabels.assets.bytype') }}',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
            dataType: 'json',
            success: function (data) {
                var values = data.datasets && data.datasets[0] ? data.datasets[0].data : [];
                var total = values.reduce(function (sum, value) { return sum + Number(value || 0); }, 0);
                if (!total) {
                    showChartState('statusChartWrap', 'statusChartState', '{{ trans('killa.no_asset_status') }}');
                    return;
                }

                var summary = document.getElementById('statusChartSummary');
                if (summary) {
                    summary.textContent = data.labels.map(function (label, index) {
                        return label + ': ' + Number(values[index] || 0);
                    }).join(', ');
                }

                // the center total comes from the server-rendered metrics
                // (the tile count) — the API buckets overlap, so summing
                // slices would overstate the real total
                var totalEl = document.getElementById('statusChartTotal');
                if (totalEl) {
                    totalEl.parentElement.hidden = false;
                }

                // click a slice → filtered hardware list. Only the by-type
                // payload maps to status_type filters; by-name labels carry
                // no ids, so those slices stay inert. The API drops
                // zero-count buckets, so match on the translated label
                // prefix instead of the slice index.
                @php
                    $statusLabelMap = [
                        ['label' => trans('general.ready_to_deploy'), 'key' => 'RTD'],
                        ['label' => trans('general.deployed'), 'key' => 'Deployed'],
                        ['label' => trans('general.archived'), 'key' => 'Archived'],
                        ['label' => trans('general.pending'), 'key' => 'Pending'],
                        ['label' => trans('general.undeployable'), 'key' => 'Undeployable'],
                    ];
                @endphp
                var statusChartMode = '{{ $snipeSettings->dash_chart_type == 'name' ? 'name' : 'type' }}';
                var statusLabelMap = @json($statusLabelMap);
                var hardwareBase = '{{ route('hardware.index') }}';

                // [killa-fix-ui] one accent per chart: Deployed carries the
                // brand red, every other status steps down the neutral ramp
                // instead of a rainbow of statuslabel colors
                var killaDeployedLabel = '{{ trans('general.deployed') }}';
                var killaNeutralSteps = ['#9ca3af', '#c4c9d1', '#6b7280', '#d9dce1', '#4b5563'];
                var killaNeutralIndex = 0;
                var killaSliceColors = (data.labels || []).map(function (label) {
                    if (label.indexOf(killaDeployedLabel) === 0) return cssVar('--k-accent');
                    var color = killaNeutralSteps[killaNeutralIndex % killaNeutralSteps.length];
                    killaNeutralIndex++;
                    return color;
                });
                if (data.datasets && data.datasets[0]) {
                    data.datasets[0].backgroundColor = killaSliceColors;
                    data.datasets[0].borderColor = killaSliceColors;
                }

                new Chart(statusCanvas.getContext('2d'), {
                    type: 'doughnut',
                    data: data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutoutPercentage: 68,
                        legend: {
                            position: 'bottom',
                            labels: {boxWidth: 10, usePointStyle: true},
                        },
                        hover: {
                            onHover: function (event, active) {
                                if (statusChartMode === 'type') {
                                    event.target.style.cursor = active && active.length ? 'pointer' : 'default';
                                }
                            },
                        },
                        onClick: function (event, active) {
                            if (statusChartMode !== 'type' || !active || !active.length) return;
                            var label = data.labels[active[0]._index] || '';
                            for (var i = 0; i < statusLabelMap.length; i++) {
                                if (label.indexOf(statusLabelMap[i].label) === 0) {
                                    window.location.href = hardwareBase + '?status_type=' + statusLabelMap[i].key;
                                    return;
                                }
                            }
                        },
                        tooltips: {
                            callbacks: {
                                label: function (tooltipItem, chartData) {
                                    var value = Number(chartData.datasets[0].data[tooltipItem.index] || 0);
                                    return (chartData.labels[tooltipItem.index] || '') + ': ' + value + ' (' + Math.round(value / total * 100) + '%)';
                                },
                            },
                        },
                    },
                });
            },
            error: function () {
                showChartState('statusChartWrap', 'statusChartState', '{{ trans('killa.chart_unavailable') }}');
            },
        });
    });
</script>
@endpush
@endcan
