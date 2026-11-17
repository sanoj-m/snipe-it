<div class="box box-default">
    <div class="box-header with-border">
        <h2 class="box-title">{{ trans('general.dashboard_attention') }}</h2>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse" aria-hidden="true">
                <x-icon type="minus"/>
                <span class="sr-only">{{ trans('general.collapse') }}</span>
            </button>
        </div>
    </div>
    <div class="box-body">
        {{-- Two-column layout inside each row: label flexes to fill,
             badge column has a fixed min-width so "1" and "1,247" line
             up in the same vertical spine instead of the label text
             bouncing around with the badge width. See
             .dashboard-attention-list in overrides.less. --}}
        <ul class="list-group list-group-unbordered dashboard-attention-list" style="margin-bottom: 0;">
            @if ($overdueAudits + $overdueCheckins + $overdueMaintenances + $pendingAcceptancesCount + $pendingRequestsCount + $licensesExpiringSoon + $assetsPastEol + $usersOffboardingSoon === 0)
                <li class="list-group-item dashboard-attention-clear">
                    <x-icon type="checkmark" class="fa-fw"/>
                    <span class="dashboard-attention-label">{{ trans('killa.all_clear') }}</span>
                </li>
            @endif
            @can('audit', \App\Models\Asset::class)
                <li class="list-group-item{{ $overdueAudits === 0 ? ' is-zero' : '' }}">
                    <a href="{{ route('assets.audit.due') }}">
                        <x-icon type="audit" class="fa-fw"/>
                        <span class="dashboard-attention-label">{{ trans('general.dashboard_overdue_audits') }}</span>
                        <span class="badge dashboard-attention-count">{{ number_format($overdueAudits) }}</span>
                    </a>
                </li>
            @endcan
            @can('checkin', \App\Models\Asset::class)
                <li class="list-group-item{{ $overdueCheckins === 0 ? ' is-zero' : '' }}">
                    {{-- #overdue hash preselects the "overdue" tab via
                         snipeit.js's deep-link handler, so the landing
                         view matches the count semantics. Default tab
                         is "due" (upcoming), not overdue. --}}
                    <a href="{{ route('assets.checkins.due').'#overdue' }}">
                        <x-icon type="due" class="fa-fw"/>
                        <span class="dashboard-attention-label">{{ trans('general.dashboard_overdue_checkins') }}</span>
                        <span class="badge dashboard-attention-count">{{ number_format($overdueCheckins) }}</span>
                    </a>
                </li>
            @endcan
                @can('canCheckoutAtLeastOneItemType')
                <li class="list-group-item{{ $pendingAcceptancesCount === 0 ? ' is-zero' : '' }}">
                    <a href="{{ route('unaccepted-acceptances.index') }}">
                        <x-icon type="signature" class="fa-fw"/>
                        <span class="dashboard-attention-label">{{ trans('general.dashboard_unaccepted_assets') }}</span>
                        <span class="badge dashboard-attention-count">{{ number_format($pendingAcceptancesCount) }}</span>
                    </a>
                </li>
                @endcan
            @can('canCheckoutAtLeastOneItemType')
                <li class="list-group-item{{ $pendingRequestsCount === 0 ? ' is-zero' : '' }}">
                    <a href="{{ route('requests.index') }}">
                        <x-icon type="pending-requests" class="fa-fw"/>
                        <span class="dashboard-attention-label">{{ trans('general.pending_requests') }}</span>
                        <span class="badge dashboard-attention-count">{{ number_format($pendingRequestsCount) }}</span>
                    </a>
                </li>
            @endcan

            @can('view', \App\Models\Asset::class)
                <li class="list-group-item{{ $assetsPastEol === 0 ? ' is-zero' : '' }}">
                    <a href="{{ $assetsPastEol > 0 ? route('hardware.index', ['past_eol' => 1]) : route('hardware.index') }}">
                        <x-icon type="due" class="fa-fw"/>
                        <span class="dashboard-attention-label">{{ trans('general.dashboard_assets_past_eol') }}</span>
                        <span class="badge dashboard-attention-count">{{ number_format($assetsPastEol) }}</span>
                    </a>
                </li>
                <li class="list-group-item{{ $overdueMaintenances === 0 ? ' is-zero' : '' }}">
                    <a href="{{ route('maintenances.index', ['upcoming_status' => 'overdue']) }}">
                        <x-icon type="maintenances" class="fa-fw"/>
                        <span class="dashboard-attention-label">{{ trans('general.dashboard_overdue_maintenances') }}</span>
                        <span class="badge dashboard-attention-count">{{ number_format($overdueMaintenances) }}</span>
                    </a>
                </li>
            @endcan
            @can('view', \App\Models\License::class)
                <li class="list-group-item{{ $licensesExpiringSoon === 0 ? ' is-zero' : '' }}">
                    <a href="{{ route('licenses.index', ['status' => 'expiring']) }}">
                        <x-icon type="license" class="fa-fw"/>
                        <span class="dashboard-attention-label">{{ trans('general.dashboard_licenses_expiring_soon') }}</span>
                        <span class="badge dashboard-attention-count">{{ number_format($licensesExpiringSoon) }}</span>
                    </a>
                </li>
            @endcan
            @can('view', \App\Models\User::class)
                <li class="list-group-item{{ $usersOffboardingSoon === 0 ? ' is-zero' : '' }}">
                    <a href="{{ route('users.index', ['end_date_within' => \App\Livewire\NeedsAttention::OFFBOARDING_DAYS, 'with_assignments' => 1]) }}">
                        <x-icon type="offboarding" class="fa-fw"/>
                        <span class="dashboard-attention-label">{{ trans('general.dashboard_users_offboarding_soon') }}</span>
                        <span class="badge dashboard-attention-count">{{ number_format($usersOffboardingSoon) }}</span>
                    </a>
                </li>
            @endcan
        </ul>
    </div>
</div>
