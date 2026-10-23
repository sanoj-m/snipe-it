@extends('layouts/default')

{{-- [killa-ux] Redesigned licenses list (KCP-003 amendment). Server-rendered,
     styled by public/css/killa-ux.css under the .kx wrapper. All strings via
     killa.licenses_ux.* --}}

@section('title')
{{ trans('killa.licenses_ux.title') }}
@parent
@stop

@section('header_right')
@stop

@section('content')
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

    {{-- Groups / rows --}}
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
                    <div class="kx-group-rows">
                        @foreach ($rows as $row)
                            <div class="kx-row" data-id="{{ $row['id'] }}">
                                <span class="kx-handle" title="{{ trans('killa.licenses_ux.reorganize') }}"><i class="fas fa-grip-vertical" aria-hidden="true"></i></span>

                                @can('delete', \App\Models\License::class)
                                    <input type="checkbox" class="kx-bulk-check hidden-print" data-id="{{ $row['id'] }}" aria-label="{{ $row['name'] }}">
                                @endcan

                                <div class="kx-row-main">
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
                                    @if ($row['manufacturer'] || $row['serial'])
                                        <div class="kx-row-sub">
                                            {{ collect([$row['manufacturer'], $row['serial']])->filter()->implode(' · ') }}
                                        </div>
                                    @endif
                                </div>

                                <div class="kx-cell kx-cell-location kx-muted">
                                    <span class="kx-cell-label">{{ trans('killa.licenses_ux.location') }}:</span>
                                    {{ $row['location'] ?? '—' }}
                                </div>

                                <div class="kx-cell kx-cell-term">
                                    <span class="kx-cell-label">{{ trans('killa.licenses_ux.term') }}:</span>
                                    {{ $row['term'] ?? '—' }}
                                </div>

                                <div class="kx-cell kx-cell-seats kx-seats">
                                    <span class="kx-cell-label">{{ trans('killa.licenses_ux.seats') }}:</span>
                                    <div>
                                        <div class="kx-seatbar" role="img" aria-label="{{ trans('killa.licenses_ux.seats_text', ['used' => $row['seats_used'], 'total' => $row['seats']]) }}">
                                            <span class="kx-seatbar-fill @if ($row['seats'] > 0 && $row['seats_available'] === 0) kx-seatbar-full @endif" style="width: {{ $row['seats'] > 0 ? min(100, round($row['seats_used'] / $row['seats'] * 100)) : 0 }}%"></span>
                                        </div>
                                        <span class="kx-seats-text">{{ trans('killa.licenses_ux.seats_text', ['used' => number_format($row['seats_used']), 'total' => number_format($row['seats'])]) }}</span>
                                    </div>
                                </div>

                                <div class="kx-cell kx-cell-cost kx-cost">
                                    <span class="kx-cell-label">{{ trans('killa.licenses_ux.cost') }}:</span>
                                    @if ($row['cost_yearly'] !== null)
                                        <div>
                                            <span class="kx-cost-main">{{ trans('killa.licenses_ux.cost_per_month', ['currency' => $row['currency'] ?? $base_currency, 'amount' => number_format($row['cost_monthly'], 2)]) }}</span>
                                            @if ($row['cost_monthly_base'] !== null)
                                                <span class="kx-cost-conv">{{ trans('killa.currency.aed_equivalent', ['base' => $base_currency, 'amount' => number_format($row['cost_monthly_base'], 2)]) }}</span>
                                            @endif
                                            <span class="kx-cost-conv">{{ trans('killa.licenses_ux.cost_per_year', ['currency' => $row['currency'] ?? $base_currency, 'amount' => number_format($row['cost_yearly'], 2)]) }}</span>
                                        </div>
                                    @else
                                        —
                                    @endif
                                </div>

                                <div class="kx-cell kx-cell-expiry">
                                    <span class="kx-cell-label">{{ trans('killa.licenses_ux.expiry') }}:</span>
                                    <div class="kx-expiry">
                                        @if ($row['days'] === null)
                                            <span class="kx-muted">{{ trans('killa.licenses_ux.no_expiry') }}</span>
                                        @else
                                            <span class="kx-num">{{ $row['expiry'] }}</span>
                                            @if ($row['days'] < 0)
                                                <span class="kx-pill kx-pill-danger">{{ trans('killa.licenses_ux.expired') }}</span>
                                            @else
                                                <span class="kx-pill kx-pill-{{ $row['status'] }}">{{ trans('killa.licenses_ux.days', ['days' => number_format($row['days'])]) }}</span>
                                            @endif
                                        @endif
                                    </div>
                                </div>

                                <div class="kx-row-actions hidden-print">
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
                            </div>
                        @endforeach
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
        // Bulk selection: mirror checked rows into hidden ids[] inputs on submit
        var $bulkbar = $('#kx-bulkbar');
        if ($bulkbar.length) {
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
        // Drag-to-reorder (recovered Renewal Tracker UX): per-group sortable,
        // handle-gripped, cancel + status message on save error
        var $toggle = $('#kx-reorder-toggle');
        var $status = $('#kx-reorder-status');
        var $groups = $('#kx-groups');
        var reordering = false;

        var saveOrder = function (container) {
            var order = $(container).find('.kx-row').map(function () {
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
                    $(container).sortable('cancel');
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
                $groups.find('.kx-group-rows').sortable({
                    items: '> .kx-row',
                    handle: '.kx-handle',
                    placeholder: 'kx-sortable-placeholder',
                    stop: function (e, ui) {
                        saveOrder(ui.item.closest('.kx-group-rows'));
                    }
                }).disableSelection();
            } else {
                $groups.find('.kx-group-rows').each(function () {
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
