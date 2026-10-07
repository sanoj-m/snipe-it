{{-- Floating-licenses addon: info rows inside the license info list.
     Included from the core licenses/view.blade.php x-info-panel default slot;
     expects $explicitFloatingConfig, $floatingConfig, $floatingStats,
     $floatingCostPerUser, $snipeSettings. --}}
<x-info-element icon_type="licenses" title="{{ trans('floating-licenses::floating.license_type') }}">
    {{ trans('floating-licenses::floating.license_type') }}
    <span class="pull-right">
        {{ $explicitFloatingConfig ? trans('floating-licenses::floating.type_floating') : trans('floating-licenses::floating.type_fixed') }}
        @if ($floatingConfig && $floatingStats['over_allocated'])
            <span class="label label-warning" data-tooltip="true" title="{{ trans('floating-licenses::floating.over_allocated_label', ['assigned' => $floatingStats['active'], 'pool' => $floatingStats['pool_size'], 'excess' => $floatingStats['excess']]) }}">+{{ $floatingStats['excess'] }}</span>
        @endif
    </span>
</x-info-element>

@if ($floatingConfig)
    <x-info-element icon_type="seats" title="{{ trans('floating-licenses::floating.pool_size') }}">
        {{ trans('floating-licenses::floating.pool_size') }}
        <span class="pull-right">{{ $floatingStats['pool_size'] }}</span>
    </x-info-element>

    <x-info-element icon_type="users" title="{{ trans('floating-licenses::floating.assigned_users') }}">
        {{ trans('floating-licenses::floating.assigned_users') }}
        <span class="pull-right">{{ $floatingStats['active'] }}</span>
    </x-info-element>

    <x-info-element icon_type="available" title="{{ trans('floating-licenses::floating.available') }}">
        {{ trans('floating-licenses::floating.available') }}
        <span class="pull-right">{{ $floatingStats['pool_size'] - $floatingStats['active'] }}</span>
    </x-info-element>

    @can('floating_licenses.costs')
        <x-info-element icon_type="cost" title="{{ trans('floating-licenses::floating.total_cost') }}">
            {{ trans('floating-licenses::floating.total_cost') }}
            <span class="pull-right">{{ $snipeSettings->default_currency }} {{ \App\Helpers\Helper::formatCurrencyOutput($floatingConfig->total_cost) }}</span>
        </x-info-element>

        <x-info-element icon_type="cost" title="{{ trans('floating-licenses::floating.cost_per_user') }}">
            {{ trans('floating-licenses::floating.cost_per_user') }}
            <span class="pull-right">{{ $snipeSettings->default_currency }} {{ \App\Helpers\Helper::formatCurrencyOutput($floatingCostPerUser) }}</span>
        </x-info-element>
    @endcan
@endif
