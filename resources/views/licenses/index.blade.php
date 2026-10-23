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
