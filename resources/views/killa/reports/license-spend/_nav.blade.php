{{-- [license-spend addon] shared sub-navigation for the License Spend report pages --}}
<ul class="nav nav-tabs" style="margin-bottom: 15px;">
    <li class="{{ request()->routeIs('reports.license-spend') ? 'active' : '' }}">
        <a href="{{ route('reports.license-spend') }}">{{ trans('killa.license_spend.report_title') }}</a>
    </li>
    <li class="{{ request()->routeIs('reports.license-spend.forecast') ? 'active' : '' }}">
        <a href="{{ route('reports.license-spend.forecast') }}">{{ trans('killa.license_spend.forecast_title') }}</a>
    </li>
    <li class="{{ request()->routeIs('reports.license-spend.ai') ? 'active' : '' }}">
        <a href="{{ route('reports.license-spend.ai') }}">{{ trans('killa.license_spend.ai_title') }}</a>
    </li>
    <li class="{{ request()->routeIs('reports.license-spend.attention') ? 'active' : '' }}">
        <a href="{{ route('reports.license-spend.attention') }}">{{ trans('killa.license_spend.attention_title') }}</a>
    </li>
</ul>
