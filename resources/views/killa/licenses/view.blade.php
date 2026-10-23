@extends('layouts/default')

{{-- [killa-ux] Redesigned license detail (KCP-018 amendment). Hero + stats in
     the .kx design language; tab panes, info panel, modals, and all
     [floating-licenses addon] includes kept verbatim from upstream
     licenses/view.blade.php so functionality is identical. --}}

@section('title')
  {{ trans('admin/licenses/general.view') }}
  - {{ $license->name }}
@parent
@stop

@section('header_right')
    <x-button.info-panel-toggle/>
@endsection

@section('content')
    {{-- [floating-licenses addon] BEGIN: data prep (master switch, pool
         config, active allocations, availability stats). Inlined here (not an
         @include) because Blade includes render in their own scope — variables
         set inside the partial do NOT leak back, and the wrappers below read
         $floatingConfig. Keep in sync with the package partial
         partials/license-view-data.blade.php. --}}
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
    {{-- [floating-licenses addon] END --}}

    {{-- [killa-ux] hero data: derived from the same variables show() passes --}}
    @php
        $kxExpiry = $license->perpetual ? null : $license->expiration_date;
        $kxDays = $kxExpiry ? (int) \Carbon\Carbon::today()->diffInDays($kxExpiry, false) : null;
        $kxStatus = 'none';
        if ($kxDays !== null) {
            $kxStatus = $kxDays <= 30 ? 'danger' : ($kxDays <= 60 ? 'warning' : 'success');
        }
        $kxCost = $license->purchase_cost !== null ? (float) $license->purchase_cost * max(1, (int) $license->seats) : null;
        $kxCurrency = $license->currency ? strtoupper($license->currency) : \App\Services\Killa\CurrencyConverter::base();
        $kxCostBase = $kxCost !== null ? \App\Services\Killa\CurrencyConverter::toBase($kxCost, $kxCurrency) : null;
    @endphp

    <div class="kx">
        <div class="kx-hero">
            <div class="kx-hero-main">
                <div class="kx-hero-title">
                    <span class="kx-dot kx-dot-{{ $kxStatus }}" aria-hidden="true"></span>
                    <h2>{{ $license->name }}</h2>
                    @if ($license->perpetual)
                        <span class="kx-pill kx-pill-info">{{ trans('killa.licenses_ux.perpetual') }}</span>
                    @elseif ($kxDays !== null && $kxDays < 0)
                        <span class="kx-pill kx-pill-danger">{{ trans('killa.licenses_ux.expired') }}</span>
                    @elseif ($kxDays !== null)
                        <span class="kx-pill kx-pill-{{ $kxStatus }}">{{ trans('killa.licenses_ux.days', ['days' => number_format($kxDays)]) }}</span>
                    @endif
                    @if ($floatingConfig)
                        <span class="kx-pill kx-pill-neutral">{{ trans('killa.licenses_ux.floating') }}</span>
                    @endif
                </div>
                <div class="kx-hero-sub kx-muted">
                    {{ collect([$license->category?->name, $license->manufacturer?->name, $license->location?->name])->filter()->implode(' · ') }}
                </div>
            </div>

            <div class="kx-hero-stats">
                <div class="kx-stat">
                    <span class="kx-stat-label">{{ trans('killa.licenses_ux.seats') }}</span>
                    <span class="kx-stat-value kx-num">{{ number_format($checkedout_seats_count) }} / {{ number_format($total_seats_count) }}</span>
                    <span class="kx-stat-sub">{{ trans('killa.licenses_ux.kpi_seats_sub', ['used' => number_format($checkedout_seats_count), 'available' => number_format($available_seats_count)]) }}</span>
                </div>
                <div class="kx-stat">
                    <span class="kx-stat-label">{{ trans('killa.licenses_ux.cost') }}</span>
                    @if ($kxCost !== null)
                        <span class="kx-stat-value kx-num">{{ $kxCurrency }} {{ number_format($kxCost, 2) }}</span>
                        <span class="kx-stat-sub">
                            {{ trans('killa.licenses_ux.cost_per_year_short') }}
                            @if ($kxCostBase !== null)
                                · {{ trans('killa.currency.aed_equivalent', ['base' => \App\Services\Killa\CurrencyConverter::base(), 'amount' => number_format($kxCostBase, 2)]) }}
                            @endif
                        </span>
                    @else
                        <span class="kx-stat-value kx-muted">—</span>
                    @endif
                </div>
                <div class="kx-stat">
                    <span class="kx-stat-label">{{ trans('killa.licenses_ux.expiry') }}</span>
                    @if ($kxExpiry)
                        <span class="kx-stat-value kx-num">{{ $kxExpiry->format('d/m/Y') }}</span>
                    @else
                        <span class="kx-stat-value kx-muted">{{ trans('killa.licenses_ux.no_expiry') }}</span>
                    @endif
                </div>
            </div>

            <div class="kx-hero-actions hidden-print">
                @can('update', $license)
                    <a class="kx-btn" href="{{ route('licenses.edit', $license->id) }}">
                        <i class="fas fa-pencil-alt" aria-hidden="true"></i> {{ trans('killa.licenses_ux.edit') }}
                    </a>
                @endcan
                @can('clone', $license)
                    <a class="kx-btn" href="{{ route('clone/license', $license->id) }}">
                        <i class="fas fa-clone" aria-hidden="true"></i> {{ trans('killa.licenses_ux.clone') }}
                    </a>
                @endcan
                @can('checkout', $license)
                    @if (($license->availCount()->count() > 0) && (!$license->isInactive()))
                        <a class="kx-btn kx-btn-primary" href="{{ route('licenses.checkout', $license->id) }}">
                            <i class="fas fa-sign-out-alt" aria-hidden="true"></i> {{ trans('killa.licenses_ux.checkout') }}
                        </a>
                    @endif
                @endcan
            </div>
        </div>
    </div>

    <x-container columns="2">
        <x-page-column class="col-md-9 main-panel">
            <div class="kx kx-flush">
            <x-tabs>
                <x-slot:tabnav>

                    <x-tabs.nav-item
                            name="seats"
                            icon_type="checkedout"
                            label="{{ trans('general.assigned') }}"
                            count="{{ $license->assignedCount()->count() }}"
                    />

                    @can('checkout', $license)
                    {{-- [floating-licenses addon] no per-seat "available" tab for floating licenses --}}
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

                        {{-- [floating-licenses addon] seats table suppressed for floating licenses --}}
                        @if (! $floatingConfig)
                        <x-table
                            fixed_right_number="1"
                            fixed_number="1"
                            api_url="{{ route('api.licenses.seats.index', [$license->id, 'status' => 'assigned']) }}"
                            :presenter="\App\Presenters\LicensePresenter::dataTableLayoutSeats()"
                            export_filename="export-{{ str_slug($license->name) }}-assigned-{{ date('Y-m-d') }}"
                        />
                        @endif

                        {{-- [floating-licenses addon] BEGIN: floating assignments rendered
                             as THE assigned table (single header set + single bulk-checkin bar) --}}
                        @include('floating-licenses::partials.license-view-assignments')
                        {{-- [floating-licenses addon] END --}}

                    </x-tabs.pane>


                    @can('checkout', $license)
                    {{-- [floating-licenses addon] available pane suppressed for floating licenses --}}
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
            </div>
        </x-page-column>

        <x-page-column class="col-md-3">
            <x-box class="side-box expanded">
                <x-info-panel :infoPanelObj="$license" img_path="{{ app('licenses_upload_url') }}" :qr_code_url="route('qr_code/common', ['object_type' => 'licenses', 'id' => $license->id])">

                    {{-- [floating-licenses addon] BEGIN: info rows inside the license info list --}}
                    @include('floating-licenses::partials.license-view-info')
                    {{-- [floating-licenses addon] END --}}


                    <x-slot:buttons>
                        <x-button.edit :item="$license" :route="route('licenses.edit', $license->id)"/>
                        <x-button.clone :item="$license" :route="route('clone/license', $license->id)"/>
                        <x-button.checkout permission="checkout" :item="$license" :route="route('licenses.checkout', $license->id)" />

                        {{-- [floating-licenses addon] BEGIN: bulk user actions
                             dropdown + per-license user CSV import modal --}}
                        @include('floating-licenses::partials.license-view-actions')
                        {{-- [floating-licenses addon] END --}}

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
    <x-modals.confirm-action
        modal-name="checkinFromAllModal"
        :route="route('licenses.bulkcheckin', $license->id)"
        :title="trans('general.modal_confirm_generic')"
    >
        {{ trans_choice('admin/licenses/general.bulk.checkin_all.modal', 2, ['checkedout_seats_count' => $checkedout_seats_count]) }}
    </x-modals.confirm-action>
@endcan

@can('checkout', \App\Models\License::class)
    <x-modals.confirm-action
        modal-name="checkoutFromAllModal"
        :route="route('licenses.bulkcheckout', $license->id)"
        :title="trans('general.modal_confirm_generic')"
    >
        {{ trans_choice('admin/licenses/general.bulk.checkout_all.modal', 2, ['available_seats_count' => $available_seats_count]) }}
    </x-modals.confirm-action>
@endcan

{{-- [floating-licenses addon] BEGIN: client-side override of the core
     "Remaining" info row + bulk-select/pagination JS for the floating
     assignments table (no output when there is no pool config) --}}
@include('floating-licenses::partials.license-view-scripts')
{{-- [floating-licenses addon] END --}}

@endsection

@section('moar_scripts')
    @can('files', $license)
        <x-modals.upload-file item-type="licenses" :item-id="$license->id" />
    @endcan

    @include ('partials.bootstrap-table')
@endsection
