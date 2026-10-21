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
</script>
@stop
