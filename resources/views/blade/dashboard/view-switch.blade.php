@props(['active' => 'classic'])

<nav class="k-dashboard-switch {{ $active === 'insights' ? 'is-insights' : '' }}" aria-label="{{ trans('killa.dashboard_view') }}">
    <a
        href="{{ route('home') }}"
        class="k-dashboard-switch-link {{ $active === 'classic' ? 'is-active' : '' }}"
        @if ($active === 'classic') aria-current="page" @endif
    >
        <i class="fa fa-th-large" aria-hidden="true"></i>
        <span>{{ trans('killa.classic') }}</span>
    </a>
    <a
        href="{{ route('dashboard.insights') }}"
        class="k-dashboard-switch-link {{ $active === 'insights' ? 'is-active' : '' }}"
        @if ($active === 'insights') aria-current="page" @endif
    >
        <i class="fa fa-line-chart" aria-hidden="true"></i>
        <span>{{ trans('killa.insights') }}</span>
    </a>
</nav>
