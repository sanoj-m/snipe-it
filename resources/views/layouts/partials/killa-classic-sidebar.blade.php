{{-- [killa-v2 design-toggle] KCP-054 — STOCK upstream sidebar markup,
     preserved verbatim from layouts/default.blade.php @ b58de20859^ (the
     commit before KCP-049's rail replacement). Rendered instead of
     layouts/partials/killa-sidebar.blade.php when the Classic design is
     active (cookie killa_design=classic), so "Classic" shows the genuine
     pre-v2 Snipe-IT chrome: stock .main-sidebar + .sidebar-menu treeview,
     styled entirely by css/dist/all.css (no killa-v2 overlay loads in
     Classic). Menu items/gates/routes/trans keys are upstream's as of that
     commit; keep in sync if upstream sidebar items change. --}}
            <!-- Left side column. contains the logo and sidebar -->
            <aside class="main-sidebar">
                <!-- sidebar: style can be found in sidebar.less -->
                <section class="sidebar">
                    <!-- sidebar menu: : style can be found in sidebar.less -->
                    <ul class="sidebar-menu" data-widget="tree" {{ \App\Helpers\Helper::determineLanguageDirection() == 'rtl' ? 'style="margin-right:12px' : '' }}>
                        {{-- [incident-desk addon] --}}
                        @can('view', \App\Models\Asset::class)
                            <li {!! (request()->is('maintenances/incident-desk*') ? ' class="active" aria-current="page"' : '') !!} class="firstnav">
                                <a href="{{ route('maintenances.incident-desk') }}">
                                    <i class="fas fa-life-ring fa-fw" aria-hidden="true"></i>
                                    <span>{{ trans('admin/incident_desk/general.incident_desk') }}</span>
                                </a>
                            </li>
                        @endcan
                        @can('admin')
                            <li {!! (\request()->route()->getName()=='home' ? ' class="active" aria-current="page"' : '') !!} class="firstnav">
                                <a href="{{ route('home') }}">
                                    <x-icon type="dashboard" class="fa-fw" />
                                    <span>{{ trans('general.dashboard') }}</span>
                                </a>
                            </li>
                        @endcan
                        @can('index', \App\Models\Asset::class)
                                <li class="treeview{{ ((request()->is('statuslabels/*') || request()->is('hardware*')) ? ' active' : '') }}">
                                <a href="#">
                                    <x-icon type="assets" class="fa-fw" />
                                    <span>{{ trans('general.assets') }}</span>
                                    <x-icon type="angle-left" class="pull-right fa-fw"/>
                                </a>
                                <ul class="treeview-menu">
                                    <li {!! (!request()->query('status_type') && (request()->is('hardware')) ? ' class="active" aria-current="page"' : '') !!}>
                                        <a href="{{ url('hardware') }}">
                                            <x-icon type="circle" class="text-grey fa-fw"/>
                                            {{ trans('general.list_all') }}
                                            <span class="badge">
                                                {{ (isset($total_assets)) ? $total_assets : '' }}
                                            </span>
                                        </a>
                                    </li>

                                    <?php $status_navs = \App\Models\Statuslabel::where('show_in_nav', '=', 1)->withCount('assets as asset_count')->get(); ?>
                                    @if (count($status_navs) > 0)
                                        @foreach ($status_navs as $status_nav)
                                            <li{!! (request()->is('statuslabels/'.$status_nav->id) ? ' class="active" aria-current="page"' : '') !!}>
                                                <a href="{{ route('statuslabels.show', ['statuslabel' => $status_nav->id]) }}">
                                                    <i class="fas fa-circle text-grey fa-fw"
                                                       aria-hidden="true"{!! ($status_nav->color != '' ? ' style="color: '.e($status_nav->color).'"' : '') !!}></i>
                                                    {{ $status_nav->name }}
                                                    <span class="badge badge-secondary">{{ $status_nav->asset_count }}</span></a></li>
                                        @endforeach
                                    @endif


                                    <li id="deployed-sidenav-option" {!! (request()->query('status_type') == 'Deployed' ? ' class="active" aria-current="page"' : '') !!}>
                                        <a href="{{ url('hardware?status_type=Deployed') }}">
                                            <x-icon type="circle" class="text-blue fa-fw" />
                                            {{ trans('general.deployed') }}
                                            <span class="badge">{{ (isset($total_deployed_sidebar)) ? $total_deployed_sidebar : '' }}</span>
                                        </a>
                                    </li>
                                    <li id="rtd-sidenav-option"{!! (request()->query('status_type') == 'RTD' ? ' class="active" aria-current="page"' : '') !!}>
                                        <a href="{{ url('hardware?status_type=RTD') }}">
                                            <x-icon type="circle" class="text-green fa-fw" />
                                            {{ trans('general.ready_to_deploy') }}
                                            <span class="badge">{{ (isset($total_rtd_sidebar)) ? $total_rtd_sidebar : '' }}</span>
                                        </a>
                                    </li>
                                    <li id="pending-sidenav-option"{!! (request()->query('status_type') == 'Pending' ? ' class="active" aria-current="page"' : '') !!}>
                                        <a href="{{ url('hardware?status_type=Pending') }}">
                                            <x-icon type="circle" class="text-orange fa-fw" />
                                            {{ trans('general.pending') }}
                                            <span class="badge">{{ (isset($total_pending_sidebar)) ? $total_pending_sidebar : '' }}</span>
                                        </a>
                                    </li>
                                    <li id="undeployable-sidenav-option"{!! (request()->query('status_type') == 'Undeployable' ? ' class="active" aria-current="page"' : '') !!}>
                                        <a href="{{ url('hardware?status_type=Undeployable') }}">
                                            <x-icon type="x" class="text-red fa-fw" />
                                            {{ trans('general.undeployable') }}
                                            <span class="badge">{{ (isset($total_undeployable_sidebar)) ? $total_undeployable_sidebar : '' }}</span>
                                        </a>
                                    </li>
                                    <li id="byod-sidenav-option"{!! (request()->query('status_type') == 'byod' ? ' class="active" aria-current="page"' : '') !!}>
                                        <a
                                            href="{{ url('hardware?status_type=byod') }}">
                                            <x-icon type="x" class="text-red fa-fw" />
                                            {{ trans('general.byod') }}
                                            <span class="badge">{{ (isset($total_byod_sidebar)) ? $total_byod_sidebar : '' }}</span>
                                        </a>
                                    </li>
                                    <li id="archived-sidenav-option"{!! (request()->query('status_type') == 'Archived' ? ' class="active" aria-current="page"' : '') !!}>
                                        <a
                                            href="{{ url('hardware?status_type=Archived') }}">
                                            <x-icon type="x" class="text-red fa-fw" />
                                            {{ trans('admin/hardware/general.archived') }}
                                            <span class="badge">{{ (isset($total_archived_sidebar)) ? $total_archived_sidebar : '' }}</span>
                                        </a>
                                    </li>
                                    <li id="requestable-sidenav-option"{!! (request()->query('status_type') == 'Requestable' ? ' class="active" aria-current="page"' : '') !!}>
                                        <a
                                            href="{{ url('hardware?status_type=Requestable') }}">
                                            <x-icon type="checkmark" class="text-blue fa-fw" />
                                            {{ trans('admin/hardware/general.requestable') }}
                                        </a>
                                    </li>

                                    @can('audit', \App\Models\Asset::class)
                                        <li id="audit-due-sidenav-option"{!! (request()->is('hardware/audit/due') ? ' class="active" aria-current="page"' : '') !!}>
                                            <a href="{{ route('assets.audit.due') }}">
                                                <x-icon type="audit" class="text-yellow fa-fw"/>
                                                {{ trans('general.audit_due') }}
                                                <span class="badge">{{ (isset($total_due_and_overdue_for_audit)) ? $total_due_and_overdue_for_audit : '' }}</span>
                                            </a>
                                        </li>
                                    @endcan

                                    @can('checkin', \App\Models\Asset::class)
                                    <li id="checkin-due-sidenav-option"{!! (request()->is('hardware/checkins/due') ? ' class="active" aria-current="page"' : '') !!}>
                                        <a href="{{ route('assets.checkins.due') }}">
                                            <x-icon type="due" class="text-orange fa-fw"/>
                                            {{ trans('general.checkin_due') }}
                                            <span class="badge">{{ (isset($total_due_and_overdue_for_checkin)) ? $total_due_and_overdue_for_checkin : '' }}</span>
                                        </a>
                                    </li>
                                    @endcan

                                    <li id="past-eol-sidenav-option"{!! (request()->is('hardware') && request()->boolean('past_eol') ? ' class="active" aria-current="page"' : '') !!}>
                                        <a href="{{ route('hardware.index', ['past_eol' => 1]) }}">
                                            <x-icon type="due" class="text-orange fa-fw"/>
                                            {{ trans('general.past_eol') }}
                                        </a>
                                    </li>

                                    <li class="divider">&nbsp;</li>
                                    @can('checkin', \App\Models\Asset::class)
                                        <li{!! (request()->is('hardware/quickscancheckin') ? ' class="active" aria-current="page"' : '') !!}>
                                            <a href="{{ route('hardware/quickscancheckin') }}">
                                                {{ trans('general.quickscan_checkin') }}
                                            </a>
                                        </li>
                                    @endcan

                                    @can('checkout', \App\Models\Asset::class)
                                        <li{!! (request()->is('hardware/bulkcheckout') ? ' class="active" aria-current="page"' : '') !!}>
                                            <a href="{{ route('hardware.bulkcheckout.show') }}">
                                                {{ trans('general.bulk_checkout') }}
                                            </a>
                                        </li>
                                    @endcan

                                    @can('create', \App\Models\Asset::class)
                                        <li{!! (request()->query('status_type') == 'Deleted' ? ' class="active" aria-current="page"' : '') !!}>
                                            <a href="{{ url('hardware?status_type=Deleted') }}">
                                                {{ trans('general.deleted') }}
                                            </a>
                                        </li>
                                    @endcan
                                    @can('audit', \App\Models\Asset::class)
                                        <li id="bulk-audit-sidenav-option" {!! (request()->is('hardware/bulkaudit') ? ' class="active" aria-current="page"' : '') !!}>
                                            <a href="{{ route('assets.bulkaudit') }}">
                                                {{ trans('general.bulkaudit') }}
                                            </a>
                                        </li>
                                    @endcan

                                </ul>
                                </li>
                            @endcan
                            {{-- Dedicated Maintenances section. Treeview parent
                                 so "list all" and Maintenance Types share one
                                 visual home. The @if guard OR's the two
                                 governing permissions so a user with either
                                 can see the parent. --}}
                            @if (Gate::allows('view', \App\Models\Asset::class) || Gate::allows('index', \App\Models\MaintenanceType::class))
                                <li class="treeview{{ (request()->is('maintenances*') || request()->is('maintenance-types*')) ? ' active' : '' }}">
                                    <a href="#">
                                        <x-icon type="maintenances" class="fa-fw"/>
                                        <span>{{ trans('general.maintenances') }}</span>
                                        <x-icon type="angle-left" class="pull-right fa-fw"/>
                                    </a>
                                    <ul class="treeview-menu">
                                        @can('view', \App\Models\Asset::class)
                                            <li{!! (request()->is('maintenances') ? ' class="active" aria-current="page"' : '') !!}>
                                            <a href="{{ route('maintenances.index') }}">
                                                <x-icon type="circle" class="text-grey fa-fw"/>
                                                {{ trans('general.list_all') }}
                                            </a>
                                        </li>
                                    @endcan
                                        @can('index', \App\Models\MaintenanceType::class)
                                            <li{!! (request()->is('maintenance-types*') ? ' class="active" aria-current="page"' : '') !!}>
                                                <a href="{{ route('maintenance-types.index') }}">
                                                    <x-icon type="circle" class="text-grey fa-fw"/>
                                                    {{ trans('admin/maintenance_types/general.maintenance_types') }}
                                            </a>
                                        </li>
                                    @endcan
                                    {{-- [incident-desk addon] --}}
                                        @can('view', \App\Models\Asset::class)
                                            <li{!! (request()->is('maintenances/incident-desk*') ? ' class="active" aria-current="page"' : '') !!}>
                                                <a href="{{ route('maintenances.incident-desk') }}">
                                                    <i class="fas fa-life-ring fa-fw text-grey" aria-hidden="true"></i>
                                                    {{ trans('admin/incident_desk/general.incident_desk') }}
                                            </a>
                                        </li>
                                    @endcan
                                </ul>
                                </li>
                            @endif


                        @can('view', \App\Models\License::class)
                            <li{!! (request()->is('licenses*') ? ' class="active" aria-current="page"' : '') !!}>
                                <a href="{{ route('licenses.index') }}">
                                    <x-icon type="licenses" class="fa-fw"/>
                                    <span>{{ trans('general.licenses') }}</span>
                                </a>
                            </li>
                        @endcan
                        @can('index', \App\Models\Accessory::class)
                            <li id="accessories-sidenav-option"{!! (request()->is('accessories*') ? ' class="active" aria-current="page"' : '') !!}>
                                <a href="{{ route('accessories.index') }}">
                                    <x-icon type="accessories" class="fa-fw" />
                                    <span>{{ trans('general.accessories') }}</span>
                                </a>
                            </li>
                        @endcan
                        @can('view', \App\Models\Consumable::class)
                            <li id="consumables-sidenav-option"{!! (request()->is('consumables*') ? ' class="active" aria-current="page"' : '') !!}>
                                <a href="{{ url('consumables') }}">
                                    <x-icon type="consumables" class="fa-fw" />
                                    <span>{{ trans('general.consumables') }}</span>
                                </a>
                            </li>
                        @endcan
                        @can('view', \App\Models\Component::class)
                            <li id="components-sidenav-option"{!! (request()->is('components*') ? ' class="active" aria-current="page"' : '') !!}>
                                <a href="{{ route('components.index') }}">
                                    <x-icon type="components" class="fa-fw" />
                                    <span>{{ trans('general.components') }}</span>
                                </a>
                            </li>
                        @endcan
                        @can('view', \App\Models\PredefinedKit::class)
                            <li id="kits-sidenav-option"{!! (request()->is('kits') ? ' class="active" aria-current="page"' : '') !!}>
                                <a href="{{ route('kits.index') }}">
                                    <x-icon type="kits" class="fa-fw" />
                                    <span>{{ trans('general.kits') }}</span>
                                </a>
                            </li>
                        @endcan

                        @can('view', \App\Models\User::class)
                                <li class="treeview{{ (request()->is('users*') ? ' active' : '') }}" id="users-sidenav-option">
                                    <a href="#" {{$snipeSettings->shortcuts_enabled == 1 ? "accesskey=6" : ''}}>
                                        <x-icon type="users" class="fa-fw" />
                                        <span>{{ trans('general.people') }}</span>
                                        <x-icon type="angle-left" class="pull-right fa-fw"/>
                                    </a>

                                    <ul class="treeview-menu">
                                        <li {!! ((request()->is('users')  && (request()->input() == null)) ? ' class="active" aria-current="page"' : '') !!} id="users-sidenav-list-all">
                                            <a href="{{ route('users.index') }}">
                                                <x-icon type="circle" class="text-grey fa-fw fa-fw"/>
                                                {{ trans('general.list_all') }}
                                            </a>
                                        </li>
                                        <li class="{{ (request()->is('users') && request()->input('superadmins') == "true") ? 'active' : '' }}" id="users-sidenav-superadmins">
                                            <a href="{{ route('users.index', ['superadmins' => 'true']) }}">
                                                <x-icon type="superadmin" class="text-danger fa-fw"/>
                                                {{ trans('general.show_superadmins') }}
                                            </a>
                                        </li>
                                        <li class="{{ (request()->is('users') && request()->input('admins') == "true") ? 'active' : '' }}" id="users-sidenav-list-admins">
                                            <a href="{{ route('users.index', ['admins' => 'true']) }}">
                                                <x-icon type="admin" class="text-warning fa-fw"/>
                                                {{ trans('general.show_admins') }}
                                            </a>
                                        </li>
                                        <li class="{{ (request()->is('users') && request()->input('status') == "deleted") ? 'active' : '' }}" id="users-sidenav-deleted">
                                            <a href="{{ route('users.index', ['status' => 'deleted']) }}">
                                                <x-icon type="x" class="text-danger fa-fw"/>
                                                {{ trans('general.deleted_users') }}
                                            </a>
                                        </li>
                                        <li class="{{ (request()->is('users') && request()->input('activated') == "1") ? 'active' : '' }}" id="users-sidenav-activated">
                                            <a href="{{ route('users.index', ['activated' => true]) }}">
                                                <i class="fa-solid fa-person-circle-check text-success fa-fw"></i>
                                                {{ trans('general.login_enabled') }}
                                            </a>
                                        </li>
                                        <li class="{{ (request()->is('users') && request()->input('activated') == "0") ? 'active' : '' }}" id="users-sidenav-not-activated">
                                            <a href="{{ route('users.index', ['activated' => false]) }}">
                                                <i class="fa-solid fa-person-circle-xmark text-danger fa-fw"></i>
                                                {{ trans('general.login_disabled') }}
                                            </a>
                                        </li>
                                        <li class="{{ (request()->is('users') && request()->filled('end_date_within')) ? 'active' : '' }}" id="users-sidenav-offboarding">
                                            <a href="{{ route('users.index', ['end_date_within' => \App\Livewire\NeedsAttention::OFFBOARDING_DAYS, 'with_assignments' => 1]) }}">
                                                <x-icon type="offboarding" class="text-warning fa-fw"/>
                                                {{ trans('general.offboarding_soon') }}
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                        @endcan
                            @can('canViewUsersAndCheckoutables')
                                <li{!! (request()->routeIs('calendar.index') ? ' class="active" aria-current="page"' : '') !!}>
                                    <a href="{{ route('calendar.index') }}">
                                        <x-icon type="calendar" class="fa-fw"/>
                                        <span>{{ trans('general.calendar') }}</span>
                                    </a>
                                </li>
                            @endcan
                        @can('import')
                            <li id="import-sidenav-option"{!! (request()->is('import*') ? ' class="active" aria-current="page"' : '') !!}>
                                <a href="{{ route('imports.index') }}">
                                    <x-icon type="import" class="fa-fw" />
                                    <span>{{ trans('general.import') }}</span>
                                </a>
                            </li>
                        @endcan

                        @can('backend.interact')
                            <li id="settings-sidenav-option" class="treeview {!! (request()->is(App\Helpers\Helper::SettingUrls()) ? ' active' : '') !!}">
                                <a href="#" id="settings">
                                    <x-icon type="settings" class="fa-fw" />
                                    <span>{{ trans('general.settings') }}</span>
                                    <x-icon type="angle-left" class="pull-right fa-fw"/>
                                </a>

                                <ul class="treeview-menu">
                                    @if(Gate::allows('view', App\Models\CustomField::class) || Gate::allows('view', App\Models\CustomFieldset::class))
                                        <li {!! (request()->is('fields*') ? ' class="active" aria-current="page"' : '') !!}>
                                            <a href="{{ route('fields.index') }}">
                                                {{ trans('admin/custom_fields/general.custom_fields') }}
                                            </a>
                                        </li>
                                    @endif

                                    @can('view', \App\Models\Statuslabel::class)
                                        <li {!! (request()->is('statuslabels*') ? ' class="active" aria-current="page"' : '') !!}>
                                            <a href="{{ route('statuslabels.index') }}">
                                                {{ trans('general.status_labels') }}
                                            </a>
                                        </li>
                                    @endcan

                                    @can('view', \App\Models\AssetModel::class)
                                        <li {!! (request()->is('models*') ? ' class="active" aria-current="page"' : '') !!}>
                                            <a href="{{ route('models.index') }}">
                                                {{ trans('general.asset_models') }}
                                            </a>
                                        </li>
                                    @endcan

                                    @can('view', \App\Models\Category::class)
                                        <li {!! (request()->is('categories*') ? ' class="active" aria-current="page"' : '') !!}>
                                            <a href="{{ route('categories.index') }}">
                                                {{ trans('general.categories') }}
                                            </a>
                                        </li>
                                    @endcan

                                    @can('view', \App\Models\Manufacturer::class)
                                        <li {!! (request()->is('manufacturers*') ? ' class="active" aria-current="page"' : '') !!}>
                                            <a href="{{ route('manufacturers.index') }}">
                                                {{ trans('general.manufacturers') }}
                                            </a>
                                        </li>
                                    @endcan

                                    @can('view', \App\Models\Supplier::class)
                                        <li {!! (request()->is('suppliers*') ? ' class="active" aria-current="page"' : '') !!}>
                                            <a href="{{ route('suppliers.index') }}">
                                                {{ trans('general.suppliers') }}
                                            </a>
                                        </li>
                                    @endcan

                                    @can('view', \App\Models\Department::class)
                                        <li {!! (request()->is('departments*') ? ' class="active" aria-current="page"' : '') !!}>
                                            <a href="{{ route('departments.index') }}">
                                                {{ trans('general.departments') }}
                                            </a>
                                        </li>
                                    @endcan

                                    @can('view', \App\Models\Location::class)
                                        <li {!! (request()->is('locations*') ? ' class="active" aria-current="page"' : '') !!}>
                                            <a href="{{ route('locations.index') }}">
                                                {{ trans('general.locations') }}
                                            </a>
                                        </li>
                                    @endcan

                                    @can('view', \App\Models\Company::class)
                                        <li {!! (request()->is('companies*') ? ' class="active" aria-current="page"' : '') !!}>
                                            <a href="{{ route('companies.index') }}">
                                                {{ trans('general.companies') }}
                                            </a>
                                        </li>
                                    @endcan

                                    @can('view', \App\Models\Depreciation::class)
                                        <li  {!! (request()->is('depreciations*') ? ' class="active" aria-current="page"' : '') !!}>
                                            <a href="{{ route('depreciations.index') }}">
                                                {{ trans('general.depreciation') }}
                                            </a>
                                        </li>
                                    @endcan
                                </ul>
                            </li>
                        @endcan

                        @can('reports.view')
                            <li class="treeview{{ (request()->is('reports*') ? ' active' : '') }}">

                                <a href="#" class="dropdown-toggle">
                                    <x-icon type="reports" class="fa-fw" />
                                    <span>{{ trans('general.reports') }}</span>
                                    <x-icon type="angle-left" class="pull-right"/>
                                </a>

                                <ul class="treeview-menu">
                                    <li {!! (request()->is('reports') ? ' class="active" aria-current="page"' : '') !!}>
                                        <a href="{{ route('reports.index') }}">
                                            {{ trans('general.list_all') }}
                                        </a>
                                    </li>
                                    <li {!! (request()->is('reports/activity') ? ' class="active" aria-current="page"' : '') !!}>
                                        <a href="{{ route('reports.activity') }}">
                                            {{ trans('general.activity_report') }}
                                        </a>
                                    </li>
                                    <li {!! (request()->is('reports/custom') ? ' class="active" aria-current="page"' : '') !!}>
                                        <a href="{{ url('reports/custom') }}">
                                            {{ trans('general.custom_report') }}
                                        </a>
                                    </li>
                                    <li {!! (request()->routeIs('reports.custom.component') ? ' class="active" aria-current="page"' : '') !!}>
                                        <a href="{{ route('reports.custom.component') }}">
                                            {{ trans('general.custom_component_report') }}
                                        </a>
                                    </li>
                                    <li {!! (request()->routeIs('reports.custom.consumable') ? ' class="active" aria-current="page"' : '') !!}>
                                        <a href="{{ route('reports.custom.consumable') }}">
                                            {{ trans('general.custom_consumable_report') }}
                                        </a>
                                    </li>
                                    <li {!! (request()->is('reports/audit') ? ' class="active" aria-current="page"' : '') !!}>
                                        <a href="{{ route('reports.audit') }}">
                                            {{ trans('general.audit_report') }}</a>
                                    </li>
                                    <li {!! (request()->is('reports/depreciation') ? ' class="active" aria-current="page"' : '') !!}>
                                        <a href="{{ url('reports/depreciation') }}">
                                            {{ trans('general.depreciation_report') }}
                                        </a>
                                    </li>
                                    <li {!! (request()->is('reports/licenses') ? ' class="active" aria-current="page"' : '') !!}>
                                        <a href="{{ url('reports/licenses') }}">
                                            {{ trans('general.license_report') }}
                                        </a>
                                    </li>
                                    <li {!! (request()->routeIs('ui.reports.maintenances') ? ' class="active" aria-current="page"' : '') !!}>
                                        <a href="{{ route('ui.reports.maintenances') }}">
                                            {{ trans('general.asset_maintenance_report') }}
                                        </a>
                                    </li>
                                    <li  {!! (request()->is('reports/accessories') ? ' class="active" aria-current="page"' : '') !!}>
                                        <a href="{{ url('reports/accessories') }}">
                                            {{ trans('general.accessory_report') }}
                                        </a>
                                    </li>
                                    {{-- [license-spend addon] BEGIN --}}
                                    <li {!! (request()->is('reports/license-spend*') ? ' class="active" aria-current="page"' : '') !!}>
                                        <a href="{{ route('reports.license-spend') }}">
                                            {{ trans('killa.license_spend.report_title') }}
                                        </a>
                                    </li>
                                    {{-- [license-spend addon] END --}}
                                </ul>
                            </li>
                        @endcan
                            @can('canCheckoutAtLeastOneItemType')
                                <li{!! (request()->is('requests.index') ? ' class="active" aria-current="page"' : '') !!}>
                                    <a href="{{ route('requests.index') }}">
                                        <x-icon type="pending-requests" class="fa-fw"/>
                                        <span>{{ trans('general.pending_requests') }}</span>
                                    </a>
                                </li>
                                <li{!! (request()->is('unaccepted-acceptances') ? ' class="active" aria-current="page"' : '') !!}>
                                    <a href="{{ route('unaccepted-acceptances.index') }}">
                                        <x-icon type="signature" class="fa-fw"/>
                                        <span>{{ trans('general.unaccepted_asset_report') }}</span>
                                    </a>
                                </li>
                            @endcan

                        @can('viewRequestable', \App\Models\Asset::class)
                            <li{!! (request()->is('account/requestable') ? ' class="active" aria-current="page"' : '') !!}>
                                <a href="{{ route('account.requestable') }}">
                                    <x-icon type="requestable" class="fa-fw" />
                                    <span>{{ trans('general.requestable_items') }}</span>
                                </a>
                            </li>
                        @endcan


                    </ul>
                </section>
                <!-- /.sidebar -->
            </aside>
