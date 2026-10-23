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
    .k-renewals-handle-col, .k-renewals-handle { display: none; }
    .k-renewals-table.k-reordering .k-renewals-handle-col,
    .k-renewals-table.k-reordering .k-renewals-handle { display: table-cell; }
    .k-renewals-handle {
        width: 32px;
        text-align: center;
        color: var(--k-text-muted);
        cursor: grab;
    }
    .k-renewals-handle:hover, .k-renewals-handle:focus-visible { color: var(--k-accent); }
    .k-renewals-table.k-reordering tr.k-renewals-row { outline: none; }
    .k-renewals-table.k-reordering tr.k-renewals-row.ui-sortable-helper {
        display: table;
        background-color: var(--k-surface);
        box-shadow: var(--k-shadow-1);
    }
    .k-renewals-table.k-reordering tr.k-renewals-placeholder {
        background-color: var(--k-table-head-bg);
        visibility: visible !important; /* defeats jQuery UI's visibility:hidden so the drop slot stays visible */
    }
    .k-renewals-reorder-status { color: var(--k-text-muted); margin-left: var(--k-space-2); }
</style>

<x-container>
    <div class="box box-default">
        @can('update', \App\Models\License::class)
            <div class="box-header with-border">
                <div class="box-tools pull-right">
                    <span class="k-renewals-reorder-status" id="k-renewals-reorder-status" role="status" aria-live="polite"></span>
                    <button type="button" class="btn btn-default btn-sm" id="k-renewals-reorder-toggle">
                        <i class="fas fa-bars" aria-hidden="true"></i>
                        <span>{{ trans('killa.renewals.reorganize') }}</span>
                    </button>
                </div>
            </div>
        @endcan
        <div class="box-body table-responsive">
            @if ($groups->isEmpty())
                <p class="k-renewals-muted" style="padding: var(--k-space-4); margin: 0;">{{ trans('killa.renewals.empty') }}</p>
            @else
                <table class="table table-striped k-renewals-table">
                    <thead>
                        <tr>
                            <th class="k-renewals-handle-col" scope="col"><span class="sr-only">{{ trans('killa.renewals.reorganize') }}</span></th>
                            <th class="k-renewals-num" scope="col">#</th>
                            <th scope="col">{{ trans('killa.renewals.software') }}</th>
                            <th scope="col">{{ trans('general.location') }}</th>
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
                    @php($i = 0)
                    @foreach ($groups as $category => $rows)
                        <tbody>
                            <tr class="k-renewals-group">
                                <td colspan="13">{{ $category }}</td>
                            </tr>
                            @foreach ($rows as $row)
                                <tr class="k-renewals-row" data-id="{{ $row['id'] }}">
                                    <td class="k-renewals-handle"><i class="fas fa-bars" aria-hidden="true"></i></td>
                                    <td class="k-renewals-num k-renewals-muted">{{ ++$i }}</td>
                                    <td @class(['k-renewals-expired' => $row['status'] === 'danger' && $row['days'] !== null && $row['days'] < 0])>
                                        <a href="{{ $row['url'] }}">{{ $row['name'] }}</a>
                                    </td>
                                    <td class="k-renewals-muted">{{ $row['location'] ?? '—' }}</td>
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
                        </tbody>
                    @endforeach
                </table>
            @endif
        </div>
    </div>
</x-container>
@stop

@can('update', \App\Models\License::class)
    @push('js')
        <script nonce="{{ csrf_token() }}">
            $(function () {
                var $table = $('.k-renewals-table');
                var $toggle = $('#k-renewals-reorder-toggle');
                var $status = $('#k-renewals-reorder-status');
                var reordering = false;

                var fixHelperModified = function (e, tr) {
                    var $originals = tr.children();
                    var $helper = tr.clone();
                    $helper.children().each(function (index) {
                        $(this).width($originals.eq(index).width());
                    });
                    return $helper;
                };

                var renumber = function () {
                    var i = 0;
                    $table.find('tr.k-renewals-row').each(function () {
                        $(this).children('td.k-renewals-num').first().text(++i);
                    });
                };

                var saveOrder = function (tbody) {
                    var order = $(tbody).find('tr.k-renewals-row').map(function () {
                        return $(this).data('id');
                    }).get();

                    $.ajax({
                        method: 'POST',
                        url: '{{ route('licenses.renewals.reorder') }}',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: { order: order },
                        success: function () {
                            renumber();
                            $status.text(@json(trans('killa.renewals.reorder_saved')));
                        },
                        error: function () {
                            $(tbody).sortable('cancel');
                            $status.text(@json(trans('killa.renewals.reorder_failed')));
                        }
                    });
                };

                $toggle.on('click', function () {
                    reordering = !reordering;
                    $table.toggleClass('k-reordering', reordering);
                    $toggle.find('span').text(reordering
                        ? @json(trans('killa.renewals.done_reordering'))
                        : @json(trans('killa.renewals.reorganize')));
                    $status.text('');

                    if (reordering) {
                        $table.find('tbody').sortable({
                            items: 'tr.k-renewals-row',
                            handle: '.k-renewals-handle',
                            helper: fixHelperModified,
                            placeholder: 'k-renewals-placeholder',
                            stop: function (e, ui) {
                                saveOrder(ui.item.closest('tbody'));
                            }
                        }).disableSelection();
                    } else {
                        $table.find('tbody').each(function () {
                            if ($(this).data('ui-sortable')) {
                                $(this).sortable('destroy');
                            }
                        });
                    }
                });
            });
        </script>
    @endpush
@endcan
