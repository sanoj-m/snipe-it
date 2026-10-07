{{-- Floating-licenses addon: floating assignments rendered as THE
     assigned table (single header set + single bulk-checkin bar).
     Included from the core licenses/view.blade.php seats tab pane;
     expects $floatingConfig, $floatingAllocations, $floatingStats. --}}
@if ($floatingConfig && $floatingAllocations->isNotEmpty())
    <div class="clearfix" style="margin:10px 0;">
        @can('checkin', \App\Models\License::class)
        <form method="POST" action="{{ route('licenses.bulkcheckin.selected') }}" id="floatingBulkCheckinForm" class="form-inline hidden-print" style="display:inline-block;">
            @csrf
            <select name="bulk_actions" class="form-control select2" style="min-width:200px;">
                <option value="checkin">{{ trans('general.checkin') }}</option>
            </select>
            <button type="submit" id="floatingBulkCheckinButton" class="btn btn-theme" disabled>{{ trans('button.go') }}</button>
            <span id="floatingBulkCheckinCount" style="display:none; margin-left:8px; line-height:34px;">&mdash; <span class="badge">0</span> {{ trans('general.selected') }}</span>
        </form>
        @endcan

        <div class="pull-right" style="line-height:34px;">
            <span class="label label-info" style="font-size:90%;">
                {{ $floatingStats['active'] }} / {{ $floatingStats['pool_size'] }} {{ trans('general.assigned') }}
            </span>
            @if ($floatingStats['over_allocated'])
                <span class="label label-warning" style="font-size:90%;">
                    +{{ $floatingStats['excess'] }} {{ trans('floating-licenses::floating.over_allocated_short') }}
                </span>
            @endif
        </div>
    </div>

    <table class="table table-striped" id="floatingAssignedTable">
        <thead>
            <tr>
                @can('checkin', \App\Models\License::class)
                    <th class="hidden-print" style="width:30px;"><input type="checkbox" id="floatingSelectAll" class="hidden-print" aria-label="{{ trans('general.select_all_none') }}"></th>
                @endcan
                <th>{{ trans('general.user') }}</th>
                <th>{{ trans('general.email') }}</th>
                <th>{{ trans('general.companies') }}</th>
                <th>{{ trans('general.asset') }}</th>
                <th>{{ trans('general.location') }}</th>
                <th class="hidden-print">{{ trans('general.checkin') }}/{{ trans('general.checkout') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($floatingAllocations as $allocation)
                <tr>
                    @can('checkin', \App\Models\License::class)
                        <td class="hidden-print">
                            <input type="checkbox" class="floating-allocation-checkbox hidden-print" form="floatingBulkCheckinForm" name="ids[]" value="floating:{{ $allocation->id }}">
                        </td>
                    @endcan
                    <td>@if ($allocation->user)<a href="{{ route('users.show', $allocation->user_id) }}">{{ $allocation->user->display_name }}</a>@else{{ $allocation->user_id }}@endif</td>
                    <td>{{ $allocation->user?->email }}</td>
                    <td>{{ $allocation->user?->company?->name }}</td>
                    <td>{{ $allocation->asset?->present()?->name() ?? $allocation->asset?->asset_tag }}</td>
                    <td>{{ $allocation->user?->location?->name }}</td>
                    <td class="hidden-print">
                        {{-- [floating-licenses addon] mirrors the release handler's
                             authorization: own allocation OR release permission. --}}
                        @if (($allocation->user_id === auth()->id()) || Gate::allows('floating_licenses.release'))
                            <form method="POST" action="{{ route('floating-licenses.allocations.release', $allocation) }}" style="display:inline">
                                @csrf
                                <button type="submit" class="btn btn-sm bg-purple">{{ trans('general.checkin') }}</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="clearfix hidden-print" id="floatingPager" style="margin-top:5px;">
        <span class="text-muted" id="floatingPagerInfo"></span>
        <div class="pull-right">
            <button type="button" class="btn btn-sm btn-default" id="floatingPagerPrev">&laquo;</button>
            <span id="floatingPagerPages" style="margin:0 8px;"></span>
            <button type="button" class="btn btn-sm btn-default" id="floatingPagerNext">&raquo;</button>
        </div>
    </div>
@endif
