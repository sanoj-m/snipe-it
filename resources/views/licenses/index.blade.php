@extends('layouts/default')

{{-- Page title --}}
@section('title')
{{ trans('admin/licenses/general.software_licenses') }}
@parent
@stop


{{-- Page content --}}
@section('content')
    <x-container>
        <x-box name="licenses" sr_only_title>

            <x-slot:bulkactions>
                <x-table.bulk-licenses />
            </x-slot:bulkactions>

            <x-table.licenses
                fixed_right_number="2"
                fixed_number="1"
                show_footer="true"
                show_advanced_search="true"
                {{-- [killa license-grouping] --}}
                default_sort="category"
                group_by="category_name"
                page_size="100"
                name="licenses"
                :route="route('api.licenses.index', ['status' => e(request('status'))])"/>

        </x-box>
        <x-shiftclick/>
    </x-container>
@stop

@section('moar_scripts')
@include ('partials.bootstrap-table')

{{-- [killa license-grouping] group-by extension + formatter (category_name is a
     plain string from the transformer; empty means uncategorized) --}}
<script nonce="{{ csrf_token() }}" src="{{ url('js/extensions/bootstrap-table-group-by.min.js') }}?v={{ config('version.app_version') }}"></script>
<script nonce="{{ csrf_token() }}">
    window.snipeGroupByFormatter = function (value) {
        return (value && value !== '') ? value : '{{ trans('killa.renewals.uncategorized') }}';
    };
    // [killa renewal-columns] status dot for the Days to expire column:
    // red ≤30d or expired, amber ≤60d, green beyond
    window.licenseDaysFormatter = function (value, row) {
        var raw = row.days_to_expire_raw;
        if (raw === null || raw === undefined) { return value; }
        var cls = raw <= 30 ? 'danger' : (raw <= 60 ? 'warning' : 'success');
        return '<span class="k-dot k-dot-' + cls + '" aria-hidden="true"></span>' + value;
    };

    // [killa license-grouping] toolbar toggle: grouping on = sort within
    // category groups; off = plain full-list sorting. Persisted per browser.
    $(function () {
        var $table = $('#licensesListingTable');
        var KEY = 'killaLicensesGroupBy';

        function groupingOn() {
            return localStorage.getItem(KEY) !== '0';
        }

        // refreshOptions rebuilds the toolbar, so the button must be
        // re-injected whenever the table re-renders.
        function ensureToggleButton() {
            if ($('#k-group-toggle').length) {
                return;
            }
            var $toolbar = $table.closest('.bootstrap-table').find('.fixed-table-toolbar .columns');
            $('<button type="button" id="k-group-toggle" class="btn btn-default btn-sm" ' +
                'data-tooltip="true" title="{{ trans('killa.renewals.group_toggle') }}">' +
                '<i class="fa-solid fa-layer-group" aria-hidden="true"></i>' +
                ' <span class="hidden-xs">{{ trans('killa.renewals.group_toggle') }}</span></button>')
                .on('click', function () { applyGrouping(!groupingOn()); })
                .prependTo($toolbar);
            $('#k-group-toggle')
                .toggleClass('k-toggle-on', groupingOn())
                .attr('aria-pressed', groupingOn() ? 'true' : 'false');
        }

        function applyGrouping(on) {
            var opts = $table.bootstrapTable('getOptions');
            var url = (opts.url || '').replace(/([?&])grouped=0&?/, '$1').replace(/[?&]$/, '');
            if (!on) {
                url += (url.indexOf('?') === -1 ? '?' : '&') + 'grouped=0';
            }
            localStorage.setItem(KEY, on ? '1' : '0');
            $table.bootstrapTable('refreshOptions', {
                groupBy: on,
                customSort: on ? 'snipeGroupBySorter' : null,
                url: url
            });
            ensureToggleButton();
        }

        // Runs after the shared partial's ready handler (stack renders first),
        // so the table is already initialized here.
        ensureToggleButton();
        $table.on('refresh-options.bs.table post-body.bs.table', ensureToggleButton);

        if (!groupingOn()) {
            applyGrouping(false);
        }
    });
    // [killa license-grouping] bootstrap-table's core initSort re-sorts
    // this.data client-side even with server pagination, which would break
    // group contiguity. Custom sort: category always dominant (groups
    // alphabetical), the user's column sort applies within each group.
    window.snipeGroupBySorter = function (sortName, sortOrder, data) {
        var dir = sortOrder === 'desc' ? -1 : 1;
        var val = function (row, field) {
            var v = row[field];
            if (v && typeof v === 'object') { v = v.datetime || v.date || v.name || ''; }
            return v === null || v === undefined ? '' : v;
        };
        data.sort(function (a, b) {
            var ca = val(a, 'category_name'), cb = val(b, 'category_name');
            if (ca !== cb) { return String(ca).localeCompare(String(cb)); }
            var va = val(a, sortName), vb = val(b, sortName);
            return dir * String(va).localeCompare(String(vb), undefined, { numeric: true });
        });
    };
</script>
@stop
