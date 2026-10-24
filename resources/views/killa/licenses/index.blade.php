@extends('layouts/default')

{{-- [killa-ux] Redesigned licenses list (KCP-003 amendment). Server-rendered,
     styled by public/css/killa-ux.css under the .kx wrapper. All strings via
     killa.licenses_ux.* — full column parity with the old bootstrap-table
     (LicensePresenter::dataTableLayout); columns toggle via localStorage. --}}

@section('title')
{{ trans('killa.licenses_ux.title') }}
@parent
@stop

@section('header_right')
@stop

@section('content')
@php
    // Column set mirrors LicensePresenter::dataTableLayout() (incl. the Killa
    // renewal columns), same default visibility. 'name' is always visible.
    $kxColumns = [
        'id' => ['label' => trans('general.id'), 'default' => false, 'num' => true],
        'license_type' => ['label' => trans('floating-licenses::floating.license_type'), 'default' => true],
        'company' => ['label' => trans('admin/companies/table.title'), 'default' => false],
        'product_key' => ['label' => trans('admin/licenses/form.license_key'), 'default' => true],
        'expiration_date' => ['label' => trans('admin/licenses/form.expiration'), 'default' => true],
        'days' => ['label' => trans('killa.renewals.days_to_expire'), 'default' => true, 'num' => true],
        'term' => ['label' => trans('killa.renewals.term'), 'default' => false],
        'cost_monthly' => ['label' => trans('killa.renewals.cost_monthly'), 'default' => true, 'num' => true],
        'cost_yearly' => ['label' => trans('killa.renewals.cost_yearly'), 'default' => true, 'num' => true],
        'termination_date' => ['label' => trans('admin/licenses/form.termination_date'), 'default' => false],
        'license_email' => ['label' => trans('admin/licenses/form.to_email'), 'default' => true],
        'license_name' => ['label' => trans('admin/licenses/form.to_name'), 'default' => true],
        'category' => ['label' => trans('general.category'), 'default' => false],
        'supplier' => ['label' => trans('general.supplier'), 'default' => false],
        'manufacturer' => ['label' => trans('general.manufacturer'), 'default' => true],
        'min_amt' => ['label' => trans('mail.min_QTY'), 'default' => true, 'num' => true],
        'seats' => ['label' => trans('admin/accessories/general.total'), 'default' => true, 'num' => true],
        'remaining' => ['label' => trans('admin/accessories/general.remaining'), 'default' => true, 'num' => true],
        'percent' => ['label' => '% '.trans('general.remaining'), 'default' => true],
        'purchase_date' => ['label' => trans('general.purchase_date'), 'default' => false],
        'depreciation' => ['label' => trans('admin/hardware/form.depreciation'), 'default' => false],
        'maintained' => ['label' => trans('admin/licenses/form.maintained'), 'default' => false],
        'reassignable' => ['label' => trans('admin/licenses/form.reassignable'), 'default' => false],
        'per_user_cost' => ['label' => trans('floating-licenses::floating.per_user_cost'), 'default' => true, 'num' => true],
        'unit_cost' => ['label' => trans('floating-licenses::floating.per_unit_price'), 'default' => true, 'num' => true],
        'total_cost' => ['label' => trans('floating-licenses::floating.total_cost'), 'default' => true, 'num' => true],
        'purchase_order' => ['label' => trans('admin/licenses/form.purchase_order'), 'default' => false],
        'order_number' => ['label' => trans('general.order_number'), 'default' => false],
        'created_by' => ['label' => trans('general.created_by'), 'default' => false],
        'created_at' => ['label' => trans('general.created_at'), 'default' => false],
        'updated_at' => ['label' => trans('general.updated_at'), 'default' => false],
        'notes' => ['label' => trans('general.notes'), 'default' => true],
        'requestable' => ['label' => trans('admin/hardware/general.requestable'), 'default' => false],
        'location' => ['label' => trans('killa.licenses_ux.location'), 'default' => false],
    ];
@endphp
<x-container>
<div class="kx">

    {{-- KPI strip --}}
    <div class="kx-kpis" role="region" aria-label="{{ trans('killa.licenses_ux.title') }}">
        <div class="kx-kpi">
            <div class="kx-kpi-label">{{ trans('killa.licenses_ux.kpi_total') }}</div>
            <div class="kx-kpi-value">{{ number_format($kpis['total']) }}</div>
        </div>
        <div class="kx-kpi @if ($kpis['expiring_count'] > 0) kx-kpi-alert @endif">
            <div class="kx-kpi-label">{{ trans('killa.licenses_ux.kpi_expiring') }}</div>
            <div class="kx-kpi-value">{{ number_format($kpis['expiring_count']) }}</div>
            <div class="kx-kpi-sub">{{ trans('killa.licenses_ux.kpi_cost_at_risk', ['amount' => $base_currency.' '.number_format($kpis['cost_at_risk'], 2)]) }}</div>
        </div>
        <div class="kx-kpi">
            <div class="kx-kpi-label">{{ trans('killa.licenses_ux.kpi_monthly_spend') }}</div>
            <div class="kx-kpi-value">{{ $base_currency }} {{ number_format($kpis['monthly_spend'], 2) }}</div>
            @if ($kpis['monthly_partial'])
                <div class="kx-kpi-sub">{{ trans('killa.licenses_ux.kpi_monthly_partial') }}</div>
            @endif
        </div>
        <div class="kx-kpi">
            <div class="kx-kpi-label">{{ trans('killa.licenses_ux.kpi_seats') }}</div>
            <div class="kx-kpi-value">{{ number_format($kpis['seats_total']) }}</div>
            <div class="kx-kpi-sub">{{ trans('killa.licenses_ux.kpi_seats_sub', ['used' => number_format($kpis['seats_used']), 'available' => number_format($kpis['seats_available'])]) }}</div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="kx-toolbar hidden-print">
        <form method="GET" action="{{ route('licenses.index') }}" class="kx-search" role="search">
            <input type="hidden" name="grouped" value="{{ $grouped ? '1' : '0' }}">
            <input type="search" name="search" value="{{ $search }}" placeholder="{{ trans('killa.licenses_ux.search_placeholder') }}" aria-label="{{ trans('killa.licenses_ux.search') }}">
            <button type="submit" class="kx-btn" aria-label="{{ trans('killa.licenses_ux.search') }}">
                <i class="fas fa-search" aria-hidden="true"></i>
            </button>
        </form>
        @if ($search !== '')
            <a class="kx-btn" href="{{ route('licenses.index', ['grouped' => $grouped ? '1' : '0']) }}">{{ trans('killa.licenses_ux.clear_search') }}</a>
        @endif

        <a class="kx-btn @if ($grouped) kx-btn-on @endif" href="{{ request()->fullUrlWithQuery(['grouped' => $grouped ? '0' : '1', 'page' => 1]) }}" aria-pressed="{{ $grouped ? 'true' : 'false' }}">
            <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
            {{ trans('killa.licenses_ux.group_toggle') }}
        </a>

        <span class="kx-cols">
            <button type="button" class="kx-btn" id="kx-cols-toggle" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-columns" aria-hidden="true"></i>
                {{ trans('killa.licenses_ux.columns') }}
            </button>
            <span class="kx-cols-menu" id="kx-cols-menu" hidden>
                @foreach ($kxColumns as $colKey => $col)
                    <label class="kx-cols-item">
                        <input type="checkbox" class="kx-col-check" data-col="{{ $colKey }}" @checked($col['default'])>
                        {{ $col['label'] }}
                    </label>
                @endforeach
            </span>
        </span>

        @can('update', \App\Models\License::class)
            <button type="button" class="kx-btn" id="kx-reorder-toggle" aria-pressed="false">
                <i class="fas fa-bars" aria-hidden="true"></i>
                <span>{{ trans('killa.licenses_ux.reorganize') }}</span>
            </button>
            <span class="kx-reorder-status" id="kx-reorder-status" role="status" aria-live="polite"></span>
        @endcan

        <span class="kx-spacer"></span>

        <a class="kx-btn" href="{{ route('licenses.export') }}">
            <i class="fas fa-download" aria-hidden="true"></i>
            {{ trans('killa.licenses_ux.export_csv') }}
        </a>
        @can('create', \App\Models\License::class)
            <a class="kx-btn kx-btn-primary" href="{{ route('licenses.create') }}">
                <i class="fas fa-plus" aria-hidden="true"></i>
                {{ trans('killa.licenses_ux.add_license') }}
            </a>
        @endcan
    </div>

    {{-- Bulk actions bar (posts to the existing BulkLicensesController route) --}}
    @can('delete', \App\Models\License::class)
        <form method="POST" action="{{ route('licenses.bulk.delete') }}" class="kx-bulkbar hidden-print" id="kx-bulkbar">
            @csrf
            <span class="kx-bulkbar-count" id="kx-bulk-count">0</span>
            <select name="bulk_actions" class="form-control input-sm" aria-label="{{ trans('button.bulk_actions') }}">
                <option value="delete">{{ trans('killa.licenses_ux.bulk_delete') }}</option>
                <option value="delete_with_checkin">{{ trans('killa.licenses_ux.bulk_delete_checkin') }}</option>
            </select>
            <button type="submit" class="kx-btn">{{ trans('killa.licenses_ux.bulk_go') }}</button>
        </form>
    @endcan

    {{-- Groups / table --}}
    @if ($paginator->total() === 0)
        <div class="kx-empty">
            <div class="kx-empty-icon"><i class="far fa-save" aria-hidden="true"></i></div>
            @if ($search !== '')
                <p>{{ trans('killa.licenses_ux.empty_filtered', ['search' => $search]) }}</p>
                <a class="kx-btn" href="{{ route('licenses.index') }}">{{ trans('killa.licenses_ux.clear_search') }}</a>
            @else
                <p><strong>{{ trans('killa.licenses_ux.empty_title') }}</strong></p>
                <p>{{ trans('killa.licenses_ux.empty_body') }}</p>
                @can('create', \App\Models\License::class)
                    <a class="kx-btn kx-btn-primary" href="{{ route('licenses.create') }}">
                        <i class="fas fa-plus" aria-hidden="true"></i>
                        {{ trans('killa.licenses_ux.add_license') }}
                    </a>
                @endcan
            @endif
        </div>
    @else
        <div class="kx-groups" id="kx-groups">
            @foreach ($groups as $category => $rows)
                <section class="kx-group">
                    @if ($grouped)
                        <header class="kx-group-head">
                            <h3>{{ $category }}</h3>
                            <span class="kx-group-count">{{ trans('killa.licenses_ux.group_count', ['count' => number_format($rows->count())]) }}</span>
                        </header>
                    @endif
                    <div class="kx-table-wrap">
                        <table class="kx-table">
                            <thead>
                                <tr>
                                    <th class="kx-col-handle" scope="col"><span class="sr-only">{{ trans('killa.licenses_ux.reorganize') }}</span></th>
                                    @can('delete', \App\Models\License::class)
                                        <th class="kx-col-check-th hidden-print" scope="col"><span class="sr-only">{{ trans('general.select_all_none') }}</span></th>
                                    @endcan
                                    <th scope="col">{{ trans('general.name') }}</th>
                                    @foreach ($kxColumns as $colKey => $col)
                                        <th scope="col" data-col="{{ $colKey }}" @class(['kx-hide' => ! $col['default'], 'kx-num' => $col['num'] ?? false])>{{ $col['label'] }}</th>
                                    @endforeach
                                    <th class="hidden-print" scope="col"><span class="sr-only">{{ trans('general.actions') }}</span></th>
                                </tr>
                            </thead>
                            <tbody class="kx-group-rows">
                                @foreach ($rows as $row)
                                    <tr class="kx-row" data-id="{{ $row['id'] }}">
                                        <td class="kx-col-handle"><span class="kx-handle" title="{{ trans('killa.licenses_ux.reorganize') }}"><i class="fas fa-grip-vertical" aria-hidden="true"></i></span></td>
                                        @can('delete', \App\Models\License::class)
                                            <td class="hidden-print"><input type="checkbox" class="kx-bulk-check" data-id="{{ $row['id'] }}" aria-label="{{ $row['name'] }}"></td>
                                        @endcan
                                        <td>
                                            <div class="kx-row-name">
                                                <span class="kx-dot kx-dot-{{ $row['status'] }}" aria-hidden="true"></span>
                                                <a href="{{ $row['url'] }}">{{ $row['name'] }}</a>
                                                @if ($row['perpetual'])
                                                    <span class="kx-pill kx-pill-info">{{ trans('killa.licenses_ux.perpetual') }}</span>
                                                @endif
                                                @if ($row['floating'])
                                                    <span class="kx-pill kx-pill-neutral">{{ trans('killa.licenses_ux.floating') }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td data-col="id" @class(['kx-hide' => ! $kxColumns['id']['default'], 'kx-num'])>{{ $row['id'] }}</td>
                                        <td data-col="license_type" @class(['kx-hide' => ! $kxColumns['license_type']['default']])>{{ $row['license_type'] }}</td>
                                        <td data-col="company" class="kx-hide">
                                            @if ($row['company'])
                                                <a href="{{ $row['company_url'] }}">{{ $row['company'] }}</a>
                                            @else — @endif
                                        </td>
                                        <td data-col="product_key" @class(['kx-hide' => ! $kxColumns['product_key']['default']])>
                                            @if ($row['product_key'])
                                                <code class="kx-key">{{ \Illuminate\Support\Str::limit($row['product_key'], 40) }}</code>
                                            @else — @endif
                                        </td>
                                        <td data-col="expiration_date" @class(['kx-hide' => ! $kxColumns['expiration_date']['default']])>
                                            {{ $row['expiration_formatted'] ?? '—' }}
                                        </td>
                                        <td data-col="days" @class(['kx-hide' => ! $kxColumns['days']['default'], 'kx-num'])>
                                            @if ($row['days'] === null)
                                                <span class="kx-muted">{{ trans('killa.licenses_ux.no_expiry') }}</span>
                                            @elseif ($row['days'] < 0)
                                                <span class="kx-pill kx-pill-danger">{{ trans('killa.licenses_ux.expired') }}</span>
                                            @else
                                                <span class="kx-pill kx-pill-{{ $row['status'] }}">{{ trans('killa.licenses_ux.days', ['days' => number_format($row['days'])]) }}</span>
                                            @endif
                                        </td>
                                        <td data-col="term" class="kx-hide">{{ $row['term'] ?? '—' }}</td>
                                        <td data-col="cost_monthly" @class(['kx-hide' => ! $kxColumns['cost_monthly']['default'], 'kx-num'])>
                                            @if ($row['cost_monthly'] !== null)
                                                {{ ($row['currency'] ?? $base_currency).' '.number_format($row['cost_monthly'], 2) }}
                                                @if ($row['cost_monthly_base'] !== null)
                                                    <br><small class="kx-muted">{{ trans('killa.currency.aed_equivalent', ['base' => $base_currency, 'amount' => number_format($row['cost_monthly_base'], 2)]) }}</small>
                                                @endif
                                            @else — @endif
                                        </td>
                                        <td data-col="cost_yearly" @class(['kx-hide' => ! $kxColumns['cost_yearly']['default'], 'kx-num'])>
                                            @if ($row['cost_yearly'] !== null)
                                                {{ ($row['currency'] ?? $base_currency).' '.number_format($row['cost_yearly'], 2) }}
                                                @if ($row['cost_yearly_base'] !== null)
                                                    <br><small class="kx-muted">{{ trans('killa.currency.aed_equivalent', ['base' => $base_currency, 'amount' => number_format($row['cost_yearly_base'], 2)]) }}</small>
                                                @endif
                                            @else — @endif
                                        </td>
                                        <td data-col="termination_date" class="kx-hide">{{ $row['termination_formatted'] ?? '—' }}</td>
                                        <td data-col="license_email" @class(['kx-hide' => ! $kxColumns['license_email']['default']])>
                                            @if ($row['license_email'])
                                                <a href="mailto:{{ $row['license_email'] }}">{{ $row['license_email'] }}</a>
                                            @else — @endif
                                        </td>
                                        <td data-col="license_name" @class(['kx-hide' => ! $kxColumns['license_name']['default']])>{{ $row['license_name'] ?? '—' }}</td>
                                        <td data-col="category" class="kx-hide">
                                            @if ($row['category_url'])
                                                <a href="{{ $row['category_url'] }}">{{ $row['category'] }}</a>
                                            @else {{ $row['category'] }} @endif
                                        </td>
                                        <td data-col="supplier" class="kx-hide">
                                            @if ($row['supplier'])
                                                <a href="{{ $row['supplier_url'] }}">{{ $row['supplier'] }}</a>
                                            @else — @endif
                                        </td>
                                        <td data-col="manufacturer" @class(['kx-hide' => ! $kxColumns['manufacturer']['default']])>
                                            @if ($row['manufacturer'])
                                                <a href="{{ $row['manufacturer_url'] }}">{{ $row['manufacturer'] }}</a>
                                            @else — @endif
                                        </td>
                                        <td data-col="min_amt" @class(['kx-hide' => ! $kxColumns['min_amt']['default'], 'kx-num'])>{{ $row['min_amt'] ?? '—' }}</td>
                                        <td data-col="seats" @class(['kx-hide' => ! $kxColumns['seats']['default'], 'kx-num'])>{{ number_format($row['seats']) }}</td>
                                        <td data-col="remaining" @class(['kx-hide' => ! $kxColumns['remaining']['default'], 'kx-num'])>{{ number_format($row['remaining']) }}</td>
                                        <td data-col="percent" @class(['kx-hide' => ! $kxColumns['percent']['default']])>
                                            <div class="kx-seats">
                                                <div class="kx-seatbar" role="img" aria-label="{{ $row['percent_remaining'] }}%">
                                                    <span class="kx-seatbar-fill @if ($row['percent_remaining'] === 0) kx-seatbar-full @endif" style="width: {{ $row['percent_remaining'] }}%"></span>
                                                </div>
                                                <span class="kx-seats-text">{{ $row['percent_remaining'] }}%</span>
                                            </div>
                                        </td>
                                        <td data-col="purchase_date" class="kx-hide">{{ $row['purchase_date_formatted'] ?? '—' }}</td>
                                        <td data-col="depreciation" class="kx-hide">
                                            @if ($row['depreciation'])
                                                <a href="{{ $row['depreciation_url'] }}">{{ $row['depreciation'] }}</a>
                                            @else — @endif
                                        </td>
                                        <td data-col="maintained" class="kx-hide">{{ $row['maintained'] ? trans('general.yes') : trans('general.no') }}</td>
                                        <td data-col="reassignable" class="kx-hide">{{ $row['reassignable'] ? trans('general.yes') : trans('general.no') }}</td>
                                        <td data-col="per_user_cost" @class(['kx-hide' => ! $kxColumns['per_user_cost']['default'], 'kx-num'])>{{ $row['per_user_cost'] ?? '—' }}</td>
                                        <td data-col="unit_cost" @class(['kx-hide' => ! $kxColumns['unit_cost']['default'], 'kx-num'])>{{ $row['unit_cost'] ?? '—' }}</td>
                                        <td data-col="total_cost" @class(['kx-hide' => ! $kxColumns['total_cost']['default'], 'kx-num'])>{{ $row['total_cost'] ?? '—' }}</td>
                                        <td data-col="purchase_order" class="kx-hide">{{ $row['purchase_order'] ?? '—' }}</td>
                                        <td data-col="order_number" class="kx-hide">{{ $row['order_number'] ?? '—' }}</td>
                                        <td data-col="created_by" class="kx-hide">
                                            @if ($row['created_by'])
                                                <a href="{{ $row['created_by_url'] }}">{{ $row['created_by'] }}</a>
                                            @else — @endif
                                        </td>
                                        <td data-col="created_at" class="kx-hide">{{ $row['created_at_formatted'] ?? '—' }}</td>
                                        <td data-col="updated_at" class="kx-hide">{{ $row['updated_at_formatted'] ?? '—' }}</td>
                                        <td data-col="notes" @class(['kx-hide' => ! $kxColumns['notes']['default']])>
                                            <span class="kx-muted">{!! \Illuminate\Support\Str::limit(strip_tags($row['notes_html'] ?? ''), 80) !!}</span>
                                        </td>
                                        <td data-col="requestable" class="kx-hide">{{ $row['requestable'] ? trans('general.yes') : trans('general.no') }}</td>
                                        <td data-col="location" class="kx-hide">{{ $row['location'] ?? '—' }}</td>
                                        <td class="hidden-print">
                                            <div class="kx-row-actions">
                                                <a class="kx-icon-btn" href="{{ $row['url'] }}" data-tooltip="true" title="{{ trans('killa.licenses_ux.view') }}">
                                                    <i class="fas fa-eye fa-fw" aria-hidden="true"></i><span class="sr-only">{{ trans('killa.licenses_ux.view') }}</span>
                                                </a>
                                                @can('update', $row['license'])
                                                    <a class="kx-icon-btn" href="{{ $row['edit_url'] }}" data-tooltip="true" title="{{ trans('killa.licenses_ux.edit') }}">
                                                        <i class="fas fa-pencil-alt fa-fw" aria-hidden="true"></i><span class="sr-only">{{ trans('killa.licenses_ux.edit') }}</span>
                                                    </a>
                                                @endcan
                                                @can('clone', $row['license'])
                                                    <a class="kx-icon-btn" href="{{ $row['clone_url'] }}" data-tooltip="true" title="{{ trans('killa.licenses_ux.clone') }}">
                                                        <i class="fas fa-clone fa-fw" aria-hidden="true"></i><span class="sr-only">{{ trans('killa.licenses_ux.clone') }}</span>
                                                    </a>
                                                @endcan
                                                @can('checkout', $row['license'])
                                                    @if ($row['seats_available'] > 0)
                                                        <a class="kx-icon-btn" href="{{ $row['checkout_url'] }}" data-tooltip="true" title="{{ trans('killa.licenses_ux.checkout') }}">
                                                            <i class="fas fa-sign-out-alt fa-fw" aria-hidden="true"></i><span class="sr-only">{{ trans('killa.licenses_ux.checkout') }}</span>
                                                        </a>
                                                    @endif
                                                @endcan
                                                @can('checkin', $row['license'])
                                                    @if ($row['checkin_url'])
                                                        <a class="kx-icon-btn" href="{{ $row['checkin_url'] }}" data-tooltip="true" title="{{ trans('killa.licenses_ux.checkin') }}">
                                                            <i class="fas fa-sign-in-alt fa-fw" aria-hidden="true"></i><span class="sr-only">{{ trans('killa.licenses_ux.checkin') }}</span>
                                                        </a>
                                                    @endif
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endforeach
        </div>

        <div class="kx-pagination">
            <span class="kx-page-summary">{{ trans('killa.licenses_ux.showing', ['from' => number_format($paginator->firstItem() ?? 0), 'to' => number_format($paginator->lastItem() ?? 0), 'total' => number_format($paginator->total())]) }}</span>
            {{ $paginator->links() }}
        </div>
    @endif
</div>
</x-container>
@stop

@push('js')
<script nonce="{{ csrf_token() }}">
    $(function () {
        // Column visibility, persisted per browser (old table offered the same
        // via bootstrap-table's switchable columns)
        var COLS_KEY = 'kxLicensesColsHidden';
        var $colsMenu = $('#kx-cols-menu');
        var $colsToggle = $('#kx-cols-toggle');

        var hiddenCols = [];
        try { hiddenCols = JSON.parse(localStorage.getItem(COLS_KEY) || '[]'); } catch (e) { hiddenCols = []; }

        function applyCols() {
            $('.kx-col-check').each(function () {
                var col = $(this).data('col');
                var hide = hiddenCols.indexOf(col) !== -1;
                $(this).prop('checked', !hide);
                $('#kx-groups [data-col="' + col + '"]').toggleClass('kx-hide', hide);
            });
        }

        $colsToggle.on('click', function () {
            var open = $colsMenu.prop('hidden');
            $colsMenu.prop('hidden', !open);
            $colsToggle.attr('aria-expanded', open ? 'true' : 'false');
        });
        $(document).on('click', function (e) {
            if (!$(e.target).closest('.kx-cols').length) {
                $colsMenu.prop('hidden', true);
                $colsToggle.attr('aria-expanded', 'false');
            }
        });
        $colsMenu.on('change', '.kx-col-check', function () {
            var col = $(this).data('col');
            hiddenCols = hiddenCols.filter(function (c) { return c !== col; });
            if (!$(this).is(':checked')) { hiddenCols.push(col); }
            localStorage.setItem(COLS_KEY, JSON.stringify(hiddenCols));
            applyCols();
        });
        applyCols();

        // Bulk selection: mirror checked rows into hidden ids[] inputs on submit
        var $bulkbar = $('#kx-bulkbar');
        if ($bulkbar.length) {
            var $count = $('#kx-bulk-count');
            var bulkSelectedTpl = @json(trans('killa.licenses_ux.bulk_selected', ['count' => '__N__']));
            $(document).on('change', '.kx-bulk-check', function () {
                var n = $('.kx-bulk-check:checked').length;
                $count.text(bulkSelectedTpl.replace('__N__', n));
                $bulkbar.toggleClass('kx-bulkbar-active', n > 0);
            });
            $bulkbar.on('submit', function () {
                $bulkbar.find('input[name="ids[]"]').remove();
                $('.kx-bulk-check:checked').each(function () {
                    $('<input>', { type: 'hidden', name: 'ids[]', value: $(this).data('id') }).appendTo($bulkbar);
                });
                if ($('.kx-bulk-check:checked').length === 0) {
                    return false;
                }
            });
        }

        @can('update', \App\Models\License::class)
        // Drag-to-reorder: per-group tbody sortable, handle-gripped,
        // cancel + status message on save error
        var $toggle = $('#kx-reorder-toggle');
        var $status = $('#kx-reorder-status');
        var $groups = $('#kx-groups');
        var reordering = false;

        var fixHelperModified = function (e, tr) {
            var $originals = tr.children();
            var $helper = tr.clone();
            $helper.children().each(function (index) {
                $(this).width($originals.eq(index).width());
            });
            return $helper;
        };

        var saveOrder = function (tbody) {
            var order = $(tbody).find('tr.kx-row').map(function () {
                return $(this).data('id');
            }).get();

            $.ajax({
                method: 'POST',
                url: '{{ route('licenses.reorder') }}',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: { order: order },
                success: function () {
                    $status.text(@json(trans('killa.licenses_ux.reorder_saved')));
                },
                error: function () {
                    $(tbody).sortable('cancel');
                    $status.text(@json(trans('killa.licenses_ux.reorder_failed')));
                }
            });
        };

        $toggle.on('click', function () {
            reordering = !reordering;
            $groups.toggleClass('kx-reordering', reordering);
            $toggle.attr('aria-pressed', reordering ? 'true' : 'false')
                .toggleClass('kx-btn-on', reordering);
            $toggle.find('span').text(reordering
                ? @json(trans('killa.licenses_ux.done_reordering'))
                : @json(trans('killa.licenses_ux.reorganize')));
            $status.text('');

            if (reordering) {
                $groups.find('tbody.kx-group-rows').sortable({
                    items: 'tr.kx-row',
                    handle: '.kx-handle',
                    helper: fixHelperModified,
                    placeholder: 'kx-sortable-placeholder',
                    stop: function (e, ui) {
                        saveOrder(ui.item.closest('tbody'));
                    }
                }).disableSelection();
            } else {
                $groups.find('tbody.kx-group-rows').each(function () {
                    if ($(this).data('ui-sortable')) {
                        $(this).sortable('destroy');
                    }
                });
            }
        });
        @endcan
    });
</script>
@endpush
