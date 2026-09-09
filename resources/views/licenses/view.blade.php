@extends('layouts/default')

{{-- Page title --}}
@section('title')
  {{ trans('admin/licenses/general.view') }}
  - {{ $license->name }}
  @parent
@stop

@section('header_right')
    <x-button.info-panel-toggle/>
@endsection

{{-- Page content --}}
@section('content')
    {{-- Floating-licenses addon (master switch in Admin > Settings > General).
         When the switch is on, every license behaves floating: the resolver
         lazily persists a default pool config from seats/purchase_cost. --}}
    @php
        $floatingMasterOn = (($snipeSettings->floating_licenses_enabled ?? '0') == '1');
        $explicitFloatingConfig = $floatingMasterOn
            ? \SnipeIt\FloatingLicenses\Models\FloatingLicenseConfig::where('license_id', $license->id)->first()
            : null;
        $floatingConfig = $floatingMasterOn
            ? \SnipeIt\FloatingLicenses\Support\FloatingLicenseSync::configForLicense($license)
            : null;
        $floatingService = app(\SnipeIt\FloatingLicenses\Services\FloatingLicenseService::class);
        $floatingAllocations = $floatingConfig
            ? $floatingConfig->activeAllocations()->with(['user.company', 'user.location', 'asset'])->orderBy('allocated_at', 'desc')->get()
            : collect();
        $floatingStats = $floatingConfig ? $floatingService->availability($floatingConfig) : null;
        $floatingCostPerUser = $floatingConfig ? $floatingService->costPerUser($floatingConfig) : 0.0;
    @endphp
    <x-container columns="2">
        <x-page-column class="col-md-9 main-panel">
            <x-tabs>
                <x-slot:tabnav>

                    <x-tabs.nav-item
                            name="seats"
                            icon_type="checkedout"
                            label="{{ trans('general.assigned') }}"
                            count="{{ $license->assignedCount()->count() }}"
                    />

                    @can('checkout', $license)
                    @if (! $floatingConfig)
                    <x-tabs.nav-item
                            name="available"
                            icon_type="available"
                            label="{{ trans('general.available') }}"
                            count="{{ $license->availCount()->count() }}"
                    />
                    @endif
                    @endcan

                    <x-tabs.files-tab :item="$license" count="{{ $license->uploads()->count() }}"/>
                    <x-tabs.history-tab count="{{ $license->history()->count() }}" :model="$license"/>
                    <x-tabs.upload-tab :item="$license"/>
                </x-slot:tabnav>

                <x-slot:tabpanes>

                    <x-tabs.pane name="seats">
                        <x-slot:table_header>
                            {{ trans('general.assigned') }}
                        </x-slot:table_header>

                        @can('checkin', $license)
                        {{-- [floating-licenses addon] the core seat table has no
                             rows for floating licenses — suppress its bulk bar too --}}
                        @if (! $floatingConfig)
                        <x-slot:bulkactions>
                            <x-table.bulk-actions
                                action_route="{{ route('licenses.bulkcheckin.selected') }}"
                                model_name="seat"
                            >
                                <option value="checkin">{{ trans('general.checkin') }}</option>
                            </x-table.bulk-actions>
                        </x-slot:bulkactions>
                        @endif
                        @endcan

                        @if (! $floatingConfig)
                        <x-table
                            fixed_right_number="1"
                            fixed_number="1"
                            api_url="{{ route('api.licenses.seats.index', [$license->id, 'status' => 'assigned']) }}"
                            :presenter="\App\Presenters\LicensePresenter::dataTableLayoutSeats()"
                            export_filename="export-{{ str_slug($license->name) }}-assigned-{{ date('Y-m-d') }}"
                        />
                        @endif

                        {{-- Floating-licenses addon: floating assignments rendered as THE
                             assigned table (single header set + single bulk-checkin bar) --}}
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

                    </x-tabs.pane>


                    @can('checkout', $license)
                    @if (! $floatingConfig)
                    <x-tabs.pane name="available">
                        <x-slot:table_header>
                            {{ trans('general.available') }}
                        </x-slot:table_header>

                        <x-table
                            show_search="false"
                            api_url="{{ route('api.licenses.seats.index', [$license->id, 'status' => 'available']) }}"
                            :presenter="\App\Presenters\LicensePresenter::dataTableLayoutSeats(false)"
                            export_filename="export-{{ str_slug($license->name) }}-available-{{ date('Y-m-d') }}"
                        />

                    </x-tabs.pane>
                    @endif
                    @endcan


                    <!-- start history tab pane -->
                    <x-tabs.pane name="history">
                        <x-table.history :model="$license" :route="route('api.licenses.history', $license)"/>
                    </x-tabs.pane>
                    <!-- end history tab pane -->


                    <!-- start files tab pane -->
                    <x-tabs.pane name="files">
                        <x-table.files object_type="licenses" :object="$license" />
                    </x-tabs.pane>
                    <!-- end files tab pane -->

                </x-slot:tabpanes>
            </x-tabs>
        </x-page-column>

        <x-page-column class="col-md-3">
            <x-box class="side-box expanded">
                <x-info-panel :infoPanelObj="$license" img_path="{{ app('licenses_upload_url') }}" :qr_code_url="route('qr_code/common', ['object_type' => 'licenses', 'id' => $license->id])">

                    {{-- Floating-licenses addon: info rows inside the license info list --}}
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


                    <x-slot:buttons>
                        <x-button.edit :item="$license" :route="route('licenses.edit', $license->id)"/>
                        <x-button.clone :item="$license" :route="route('clone/license', $license->id)"/>
                        <x-button.checkout permission="checkout" :item="$license" :route="route('licenses.checkout', $license->id)" />

                        {{-- Floating-licenses addon: bulk user actions dropdown --}}
                        @if (($floatingMasterOn && (Gate::allows('floating_licenses.allocate') || Gate::allows('floating_licenses.release'))) || Gate::allows('view', $license) || Gate::allows('checkout', $license))
                            <div class="dropdown" style="display: inline-block;">
                                <button type="button" class="btn btn-primary btn-sm dropdown-toggle hidden-print" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <x-icon type="users" class="fa-fw"/>
                                    {{ trans('floating-licenses::floating.bulk_actions') }} <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu">
                                    @if ($floatingMasterOn)
                                        @can('floating_licenses.allocate')
                                            <li>
                                                <a href="{{ route('floating-licenses.license.bulk-add.form', $license) }}">
                                                    {{ trans('floating-licenses::floating.bulk_add') }}
                                                </a>
                                            </li>
                                        @endcan
                                        @can('floating_licenses.release')
                                            <li>
                                                <a href="{{ route('floating-licenses.license.bulk-remove.form', $license) }}">
                                                    {{ trans('floating-licenses::floating.bulk_remove') }}
                                                </a>
                                            </li>
                                        @endcan
                                    @endif
                                    @can('view', $license)
                                        <li>
                                            <a href="{{ route('floating-licenses.license.users-export', $license) }}">
                                                {{ trans('floating-licenses::floating.export_users') }}
                                            </a>
                                        </li>
                                    @endcan
                                    @can('checkout', $license)
                                        <li>
                                            <a href="#" data-toggle="modal" data-target="#importLicenseUsersModal">
                                                {{ trans('floating-licenses::floating.import_users') }}
                                            </a>
                                        </li>
                                    @endcan
                                </ul>
                            </div>
                        @endif

                        @can('checkout', $license)
                            {{-- Floating-licenses addon: per-license user CSV import modal --}}
                            <div class="modal fade" id="importLicenseUsersModal" tabindex="-1" role="dialog" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('floating-licenses.license.users-import', $license) }}" enctype="multipart/form-data">
                                            @csrf
                                            <div class="modal-header">
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                <h4 class="modal-title">{{ trans('floating-licenses::floating.import_users') }}</h4>
                                            </div>
                                            <div class="modal-body">
                                                <p class="help-block">{{ trans('floating-licenses::floating.import_users_help') }}</p>
                                                <input type="file" name="user_list" accept=".csv,.txt" required>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('button.cancel') }}</button>
                                                <button type="submit" class="btn btn-primary">{{ trans('floating-licenses::floating.import_users') }}</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endcan

                        @can('checkout', $license)

                            @if (($license->availCount()->count() > 0) && (!$license->isInactive()))

                                <a href="#" class="btn bg-maroon btn-sm hidden-print" data-toggle="modal" data-tooltip="true" title="{{ trans('admin/licenses/general.bulk.checkout_all.enabled_tooltip') }}" data-target="#checkoutFromAllModal">
                                    <x-icon type="checkout-all" class="fa-fw"/>
                                </a>

                            @else
                                <span data-tooltip="true" title="{{ ($license->availCount()->count() == 0) ? trans('admin/licenses/general.bulk.checkout_all.disabled_tooltip') : trans('admin/licenses/message.checkout.license_is_inactive') }}" class="btn bg-maroon btn-sm hidden-print disabled" title="{{ trans('general.checkout') }}">
                                      <x-icon type="checkout-all" class="fa-fw"/>
                                  </span>
                            @endif
                        @endcan


                        @can('checkin', $license)

                            @if (($license->seats - $license->availCount()->count()) <= 0 )
                                <span data-tooltip="true" title=" {{ trans('admin/licenses/general.bulk.checkin_all.disabled_tooltip') }}">
                                        <a href="#" class="btn btn-primary bg-purple btn-sm hidden-print disabled"><x-icon type="checkin-all" class="fa-fw"/></a>
                                    </span>
                            @else
                                <a href="#" class="btn bg-purple btn-sm hidden-print" data-toggle="modal" data-tooltip="true" data-target="#checkinFromAllModal" data-content="{{ trans('general.sure_to_delete') }} title=" {{ trans('admin/licenses/general.bulk.checkin_all.button') }} data-title=" {{ trans('admin/licenses/general.bulk.checkin_all.button') }}">
                                    <x-icon type="checkin-all" class="fa-fw"/>
                                </a>
                            @endif
                        @endcan


                        <x-button.delete :item="$license" />


                    </x-slot:buttons>


                    <x-slot:before_list>




                    </x-slot:before_list>
                </x-info-panel>
            </x-box>

        </x-page-column>
    </x-container>

@can('checkin', \App\Models\License::class)
    @include ('modals.confirm-action',
          [
              'modal_name' => 'checkinFromAllModal',
              'route' => route('licenses.bulkcheckin', $license->id),
              'title' => trans('general.modal_confirm_generic'),
              'body' => trans_choice('admin/licenses/general.bulk.checkin_all.modal', 2, ['checkedout_seats_count' => $checkedout_seats_count])
          ])
@endcan

@can('checkout', \App\Models\License::class)
    @include ('modals.confirm-action',
          [
              'modal_name' => 'checkoutFromAllModal',
              'route' => route('licenses.bulkcheckout', $license->id),
              'title' => trans('general.modal_confirm_generic'),
              'body' => trans_choice('admin/licenses/general.bulk.checkout_all.modal', 2, ['available_seats_count' => $available_seats_count])
          ])
@endcan

{{-- [floating-licenses addon] Bug B: the info panel's core "Remaining" row
     (rendered by the shared x-info-panel component, which this addon must not
     edit) uses seat-based math and ignores floating allocations. Replace its
     value client-side with the floating availability (pool - active), keeping
     the same row and label; negative means over-allocated. Master-off /
     no-config renders exactly as core (this block is not output at all). --}}
@if ($floatingConfig)
    @php
        $floatingRemaining = $floatingStats['pool_size'] - $floatingStats['active'];
        $floatingRemainingHtml = ($floatingRemaining < 0)
            ? '<span class="label label-warning">'.$floatingRemaining.'</span> '.trans('general.remaining')
            : $floatingRemaining.' '.trans('general.remaining');
    @endphp
    <script id="floating-remaining-override" data-remaining="{{ $floatingRemaining }}">
        document.addEventListener('DOMContentLoaded', function () {
            // The info-element row's id is str_slug(trans('general.remaining')).
            var remainingRow = document.getElementById('remaining');
            if (remainingRow) {
                remainingRow.innerHTML = @json($floatingRemainingHtml);
                @if ($floatingRemaining < 0)
                remainingRow.classList.remove('text-success');
                remainingRow.classList.add('text-danger');
                @endif
            }
        });
    </script>

    {{-- [floating-licenses addon] bulk-select checkboxes + client-side pagination
         for the floating assignments table (posts floating:<id> to the core
         bulk-checkin route) --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var PAGE_SIZE = 25;
            var rows = Array.prototype.slice.call(document.querySelectorAll('#floatingAssignedTable tbody tr'));
            var totalPages = Math.max(1, Math.ceil(rows.length / PAGE_SIZE));
            var currentPage = 1;

            var goButton = document.getElementById('floatingBulkCheckinButton');
            var countWrap = document.getElementById('floatingBulkCheckinCount');
            var selectAll = document.getElementById('floatingSelectAll');
            var pager = document.getElementById('floatingPager');
            var pagerInfo = document.getElementById('floatingPagerInfo');
            var pagerPages = document.getElementById('floatingPagerPages');

            function visibleRows() {
                var start = (currentPage - 1) * PAGE_SIZE;
                return rows.slice(start, start + PAGE_SIZE);
            }

            function renderPage() {
                rows.forEach(function (row) { row.style.display = 'none'; });
                visibleRows().forEach(function (row) { row.style.display = ''; });

                if (pager) { pager.style.display = rows.length > PAGE_SIZE ? '' : 'none'; }
                if (pagerInfo) {
                    var start = (currentPage - 1) * PAGE_SIZE + 1;
                    var end = Math.min(rows.length, currentPage * PAGE_SIZE);
                    pagerInfo.textContent = rows.length ? ('Showing ' + start + ' to ' + end + ' of ' + rows.length + ' rows') : '';
                }
                if (pagerPages) { pagerPages.textContent = currentPage + ' / ' + totalPages; }
                document.getElementById('floatingPagerPrev').disabled = currentPage === 1;
                document.getElementById('floatingPagerNext').disabled = currentPage === totalPages;

                refreshFloatingSelection();
            }

            function refreshFloatingSelection() {
                var checked = document.querySelectorAll('.floating-allocation-checkbox:checked').length;
                if (goButton) { goButton.disabled = checked === 0; }
                if (countWrap) {
                    countWrap.querySelector('.badge').textContent = checked;
                    countWrap.style.display = checked ? 'inline' : 'none';
                }
                if (selectAll) {
                    // select-all acts on the current page's rows
                    var pageBoxes = visibleRows().map(function (row) { return row.querySelector('.floating-allocation-checkbox'); })
                        .filter(function (box) { return box; });
                    var pageChecked = pageBoxes.filter(function (box) { return box.checked; }).length;
                    selectAll.indeterminate = pageChecked > 0 && pageChecked < pageBoxes.length;
                    selectAll.checked = pageBoxes.length > 0 && pageChecked === pageBoxes.length;
                }
            }

            document.addEventListener('change', function (event) {
                if (event.target.matches('.floating-allocation-checkbox')) { refreshFloatingSelection(); }
                if (event.target === selectAll) {
                    visibleRows().forEach(function (row) {
                        var box = row.querySelector('.floating-allocation-checkbox');
                        if (box) { box.checked = selectAll.checked; }
                    });
                    refreshFloatingSelection();
                }
            });

            document.getElementById('floatingPagerPrev').addEventListener('click', function () {
                if (currentPage > 1) { currentPage--; renderPage(); }
            });
            document.getElementById('floatingPagerNext').addEventListener('click', function () {
                if (currentPage < totalPages) { currentPage++; renderPage(); }
            });

            renderPage();
        });
    </script>
@endif

@endsection

@section('moar_scripts')
    @can('files', $license)
        @include ('modals.upload-file', ['item_type' => 'licenses', 'item_id' => $license->id])
    @endcan

    @include ('partials.bootstrap-table')
@endsection
