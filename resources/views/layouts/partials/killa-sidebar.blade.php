{{-- [killa-v2 sidebar]
     Killa-owned sidebar rail (KCP-049). Replaces the upstream
     <aside class="main-sidebar"> block in layouts/default.blade.php.
     Every menu item, gate, route(), trans() key, badge and
     request()->is() active check is copied verbatim from the upstream
     block — only the wrapping markup/classes changed (ks-*).
     Registered in docs/customizations/CORE_PATCH_REGISTER.md. --}}
<aside class="ks-sidebar" id="ks-sidebar" aria-label="{{ trans('killa.navigation') }}">
    <div class="ks-brand">
        <a class="ks-brand-link" href="{{ config('app.url') }}">
            <x-icon type="assets" class="fa-fw" />
            <span class="ks-brand-name">{{ $snipeSettings->site_name }}</span>
        </a>
    </div>

    <nav class="ks-content" aria-label="{{ trans('killa.navigation') }}">

        <div class="ks-group">
            <p class="ks-group-label">{{ trans('killa.nav_group_overview') }}</p>
            <ul class="ks-menu">
                <span class="ks-pill" aria-hidden="true"></span>
                @can('admin')
                    <li {!! (\request()->route()->getName()=='home' ? ' class="ks-item active" aria-current="page"' : ' class="ks-item firstnav"') !!}>
                        <a class="ks-link" href="{{ route('home') }}">
                            <x-icon type="dashboard" class="fa-fw" />
                            <span class="ks-label">{{ trans('general.dashboard') }}</span>
                        </a>
                    </li>
                @endcan
            </ul>
        </div>

        <div class="ks-group">
            <p class="ks-group-label">{{ trans('killa.nav_group_inventory') }}</p>
            <ul class="ks-menu">
                <span class="ks-pill" aria-hidden="true"></span>
                @can('index', \App\Models\Asset::class)
                        <li class="ks-item ks-item--parent{{ ((request()->is('statuslabels/*') || request()->is('hardware*')) ? ' active' : '') }}">
                        <a class="ks-link" href="#" role="button" aria-expanded="false">
                            <x-icon type="assets" class="fa-fw" />
                            <span class="ks-label">{{ trans('general.assets') }}</span>
                            <i class="fas fa-chevron-right ks-chevron" aria-hidden="true"></i>
                        </a>
                        <ul class="ks-sub">
                            <li style="--i:0;"{!! (!request()->query('status_type') && (request()->is('hardware')) ? ' class="active" aria-current="page"' : '') !!}>
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
                                    <li style="--i:{{ $loop->iteration }};"{!! (request()->is('statuslabels/'.$status_nav->id) ? ' class="active" aria-current="page"' : '') !!}>
                                        <a href="{{ route('statuslabels.show', ['statuslabel' => $status_nav->id]) }}">
                                            <i class="fas fa-circle text-grey fa-fw"
                                               aria-hidden="true"{!! ($status_nav->color != '' ? ' style="color: '.e($status_nav->color).'"' : '') !!}></i>
                                            {{ $status_nav->name }}
                                            <span class="badge badge-secondary">{{ $status_nav->asset_count }}</span></a></li>
                                @endforeach
                            @endif


                            <li style="--i:50;" id="deployed-sidenav-option" {!! (request()->query('status_type') == 'Deployed' ? ' class="active" aria-current="page"' : '') !!}>
                                <a href="{{ url('hardware?status_type=Deployed') }}">
                                    <x-icon type="circle" class="text-blue fa-fw" />
                                    {{ trans('general.deployed') }}
                                    <span class="badge">{{ (isset($total_deployed_sidebar)) ? $total_deployed_sidebar : '' }}</span>
                                </a>
                            </li>
                            <li style="--i:51;" id="rtd-sidenav-option"{!! (request()->query('status_type') == 'RTD' ? ' class="active" aria-current="page"' : '') !!}>
                                <a href="{{ url('hardware?status_type=RTD') }}">
                                    <x-icon type="circle" class="text-green fa-fw" />
                                    {{ trans('general.ready_to_deploy') }}
                                    <span class="badge">{{ (isset($total_rtd_sidebar)) ? $total_rtd_sidebar : '' }}</span>
                                </a>
                            </li>
                            <li style="--i:52;" id="pending-sidenav-option"{!! (request()->query('status_type') == 'Pending' ? ' class="active" aria-current="page"' : '') !!}>
                                <a href="{{ url('hardware?status_type=Pending') }}">
                                    <x-icon type="circle" class="text-orange fa-fw" />
                                    {{ trans('general.pending') }}
                                    <span class="badge">{{ (isset($total_pending_sidebar)) ? $total_pending_sidebar : '' }}</span>
                                </a>
                            </li>
                            <li style="--i:53;" id="undeployable-sidenav-option"{!! (request()->query('status_type') == 'Undeployable' ? ' class="active" aria-current="page"' : '') !!}>
                                <a href="{{ url('hardware?status_type=Undeployable') }}">
                                    <x-icon type="x" class="text-red fa-fw" />
                                    {{ trans('general.undeployable') }}
                                    <span class="badge">{{ (isset($total_undeployable_sidebar)) ? $total_undeployable_sidebar : '' }}</span>
                                </a>
                            </li>
                            <li style="--i:54;" id="byod-sidenav-option"{!! (request()->query('status_type') == 'byod' ? ' class="active" aria-current="page"' : '') !!}>
                                <a
                                    href="{{ url('hardware?status_type=byod') }}">
                                    <x-icon type="x" class="text-red fa-fw" />
                                    {{ trans('general.byod') }}
                                    <span class="badge">{{ (isset($total_byod_sidebar)) ? $total_byod_sidebar : '' }}</span>
                                </a>
                            </li>
                            <li style="--i:55;" id="archived-sidenav-option"{!! (request()->query('status_type') == 'Archived' ? ' class="active" aria-current="page"' : '') !!}>
                                <a
                                    href="{{ url('hardware?status_type=Archived') }}">
                                    <x-icon type="x" class="text-red fa-fw" />
                                    {{ trans('admin/hardware/general.archived') }}
                                    <span class="badge">{{ (isset($total_archived_sidebar)) ? $total_archived_sidebar : '' }}</span>
                                </a>
                            </li>
                            <li style="--i:56;" id="requestable-sidenav-option"{!! (request()->query('status_type') == 'Requestable' ? ' class="active" aria-current="page"' : '') !!}>
                                <a
                                    href="{{ url('hardware?status_type=Requestable') }}">
                                    <x-icon type="checkmark" class="text-blue fa-fw" />
                                    {{ trans('admin/hardware/general.requestable') }}
                                </a>
                            </li>

                            @can('audit', \App\Models\Asset::class)
                                <li style="--i:57;" id="audit-due-sidenav-option"{!! (request()->is('hardware/audit/due') ? ' class="active" aria-current="page"' : '') !!}>
                                    <a href="{{ route('assets.audit.due') }}">
                                        <x-icon type="audit" class="text-yellow fa-fw"/>
                                        {{ trans('general.audit_due') }}
                                        <span class="badge">{{ (isset($total_due_and_overdue_for_audit)) ? $total_due_and_overdue_for_audit : '' }}</span>
                                    </a>
                                </li>
                            @endcan

                            @can('checkin', \App\Models\Asset::class)
                            <li style="--i:58;" id="checkin-due-sidenav-option"{!! (request()->is('hardware/checkins/due') ? ' class="active" aria-current="page"' : '') !!}>
                                <a href="{{ route('assets.checkins.due') }}">
                                    <x-icon type="due" class="text-orange fa-fw"/>
                                    {{ trans('general.checkin_due') }}
                                    <span class="badge">{{ (isset($total_due_and_overdue_for_checkin)) ? $total_due_and_overdue_for_checkin : '' }}</span>
                                </a>
                            </li>
                            @endcan

                            <li class="divider" style="--i:59;">&nbsp;</li>
                            @can('checkin', \App\Models\Asset::class)
                                <li style="--i:60;"{!! (request()->is('hardware/quickscancheckin') ? ' class="active" aria-current="page"' : '') !!}>
                                    <a href="{{ route('hardware/quickscancheckin') }}">
                                        {{ trans('general.quickscan_checkin') }}
                                    </a>
                                </li>
                            @endcan

                            @can('checkout', \App\Models\Asset::class)
                                <li style="--i:61;"{!! (request()->is('hardware/bulkcheckout') ? ' class="active" aria-current="page"' : '') !!}>
                                    <a href="{{ route('hardware.bulkcheckout.show') }}">
                                        {{ trans('general.bulk_checkout') }}
                                    </a>
                                </li>
                            @endcan

                            @can('create', \App\Models\Asset::class)
                                <li style="--i:62;"{!! (request()->query('status_type') == 'Deleted' ? ' class="active" aria-current="page"' : '') !!}>
                                    <a href="{{ url('hardware?status_type=Deleted') }}">
                                        {{ trans('general.deleted') }}
                                    </a>
                                </li>
                            @endcan
                            @can('audit', \App\Models\Asset::class)
                                <li style="--i:63;" id="bulk-audit-sidenav-option" {!! (request()->is('hardware/bulkaudit') ? ' class="active" aria-current="page"' : '') !!}>
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
                        <li class="ks-item ks-item--parent{{ (request()->is('maintenances*') || request()->is('maintenance-types*')) ? ' active' : '' }}">
                            <a class="ks-link" href="#" role="button" aria-expanded="false">
                                <x-icon type="maintenances" class="fa-fw"/>
                                <span class="ks-label">{{ trans('general.maintenances') }}</span>
                                <i class="fas fa-chevron-right ks-chevron" aria-hidden="true"></i>
                            </a>
                            <ul class="ks-sub">
                                @can('view', \App\Models\Asset::class)
                                    <li style="--i:0;"{!! (request()->is('maintenances') ? ' class="active" aria-current="page"' : '') !!}>
                                    <a href="{{ route('maintenances.index') }}">
                                        <x-icon type="circle" class="text-grey fa-fw"/>
                                        {{ trans('general.list_all') }}
                                    </a>
                                </li>
                            @endcan
                                @can('index', \App\Models\MaintenanceType::class)
                                    <li style="--i:1;"{!! (request()->is('maintenance-types*') ? ' class="active" aria-current="page"' : '') !!}>
                                        <a href="{{ route('maintenance-types.index') }}">
                                            <x-icon type="circle" class="text-grey fa-fw"/>
                                            {{ trans('admin/maintenance_types/general.maintenance_types') }}
                                    </a>
                                </li>
                            @endcan
                        </ul>
                        </li>
                    @endif


                @can('view', \App\Models\License::class)
                    <li class="ks-item"{!! (request()->is('licenses*') ? ' active" aria-current="page"' : '') !!}>
                        <a class="ks-link" href="{{ route('licenses.index') }}">
                            <x-icon type="licenses" class="fa-fw"/>
                            <span class="ks-label">{{ trans('general.licenses') }}</span>
                        </a>
                    </li>
                @endcan
                @can('index', \App\Models\Accessory::class)
                    <li class="ks-item" id="accessories-sidenav-option"{!! (request()->is('accessories*') ? ' active" aria-current="page"' : '') !!}>
                        <a class="ks-link" href="{{ route('accessories.index') }}">
                            <x-icon type="accessories" class="fa-fw" />
                            <span class="ks-label">{{ trans('general.accessories') }}</span>
                        </a>
                    </li>
                @endcan
                @can('view', \App\Models\Consumable::class)
                    <li class="ks-item" id="consumables-sidenav-option"{!! (request()->is('consumables*') ? ' active" aria-current="page"' : '') !!}>
                        <a class="ks-link" href="{{ url('consumables') }}">
                            <x-icon type="consumables" class="fa-fw" />
                            <span class="ks-label">{{ trans('general.consumables') }}</span>
                        </a>
                    </li>
                @endcan
                @can('view', \App\Models\Component::class)
                    <li class="ks-item" id="components-sidenav-option"{!! (request()->is('components*') ? ' active" aria-current="page"' : '') !!}>
                        <a class="ks-link" href="{{ route('components.index') }}">
                            <x-icon type="components" class="fa-fw" />
                            <span class="ks-label">{{ trans('general.components') }}</span>
                        </a>
                    </li>
                @endcan
                @can('view', \App\Models\PredefinedKit::class)
                    <li class="ks-item" id="kits-sidenav-option"{!! (request()->is('kits') ? ' active" aria-current="page"' : '') !!}>
                        <a class="ks-link" href="{{ route('kits.index') }}">
                            <x-icon type="kits" class="fa-fw" />
                            <span class="ks-label">{{ trans('general.kits') }}</span>
                        </a>
                    </li>
                @endcan
            </ul>
        </div>

        <div class="ks-group">
            <p class="ks-group-label">{{ trans('killa.nav_group_people_org') }}</p>
            <ul class="ks-menu">
                <span class="ks-pill" aria-hidden="true"></span>
                @can('view', \App\Models\User::class)
                        <li class="ks-item ks-item--parent{{ (request()->is('users*') ? ' active' : '') }}" id="users-sidenav-option">
                            <a class="ks-link" href="#" role="button" aria-expanded="false" {{$snipeSettings->shortcuts_enabled == 1 ? "accesskey=6" : ''}}>
                                <x-icon type="users" class="fa-fw" />
                                <span class="ks-label">{{ trans('general.people') }}</span>
                                <i class="fas fa-chevron-right ks-chevron" aria-hidden="true"></i>
                            </a>

                            <ul class="ks-sub">
                                <li style="--i:0;" {!! ((request()->is('users')  && (request()->input() == null)) ? ' class="active" aria-current="page"' : '') !!} id="users-sidenav-list-all">
                                    <a href="{{ route('users.index') }}">
                                        <x-icon type="circle" class="text-grey fa-fw fa-fw"/>
                                        {{ trans('general.list_all') }}
                                    </a>
                                </li>
                                <li style="--i:1;" class="{{ (request()->is('users') && request()->input('superadmins') == "true") ? 'active' : '' }}" id="users-sidenav-superadmins">
                                    <a href="{{ route('users.index', ['superadmins' => 'true']) }}">
                                        <x-icon type="superadmin" class="text-danger fa-fw"/>
                                        {{ trans('general.show_superadmins') }}
                                    </a>
                                </li>
                                <li style="--i:2;" class="{{ (request()->is('users') && request()->input('admins') == "true") ? 'active' : '' }}" id="users-sidenav-list-admins">
                                    <a href="{{ route('users.index', ['admins' => 'true']) }}">
                                        <x-icon type="admin" class="text-warning fa-fw"/>
                                        {{ trans('general.show_admins') }}
                                    </a>
                                </li>
                                <li style="--i:3;" class="{{ (request()->is('users') && request()->input('status') == "deleted") ? 'active' : '' }}" id="users-sidenav-deleted">
                                    <a href="{{ route('users.index', ['status' => 'deleted']) }}">
                                        <x-icon type="x" class="text-danger fa-fw"/>
                                        {{ trans('general.deleted_users') }}
                                    </a>
                                </li>
                                <li style="--i:4;" class="{{ (request()->is('users') && request()->input('activated') == "1") ? 'active' : '' }}" id="users-sidenav-activated">
                                    <a href="{{ route('users.index', ['activated' => true]) }}">
                                        <i class="fa-solid fa-person-circle-check text-success fa-fw"></i>
                                        {{ trans('general.login_enabled') }}
                                    </a>
                                </li>
                                <li style="--i:5;" class="{{ (request()->is('users') && request()->input('activated') == "0") ? 'active' : '' }}" id="users-sidenav-not-activated">
                                    <a href="{{ route('users.index', ['activated' => false]) }}">
                                        <i class="fa-solid fa-person-circle-xmark text-danger fa-fw"></i>
                                        {{ trans('general.login_disabled') }}
                                    </a>
                                </li>
                            </ul>
                        </li>
                @endcan
                    @can('canViewUsersAndCheckoutables')
                        <li class="ks-item"{!! (request()->routeIs('calendar.index') ? ' active" aria-current="page"' : '') !!}>
                            <a class="ks-link" href="{{ route('calendar.index') }}">
                                <x-icon type="calendar" class="fa-fw"/>
                                <span class="ks-label">{{ trans('general.calendar') }}</span>
                            </a>
                        </li>
                    @endcan
                @can('import')
                    <li class="ks-item" id="import-sidenav-option"{!! (request()->is('import*') ? ' active" aria-current="page"' : '') !!}>
                        <a class="ks-link" href="{{ route('imports.index') }}">
                            <x-icon type="import" class="fa-fw" />
                            <span class="ks-label">{{ trans('general.import') }}</span>
                        </a>
                    </li>
                @endcan
            </ul>
        </div>

        <div class="ks-group">
            <p class="ks-group-label">{{ trans('killa.nav_group_system') }}</p>
            <ul class="ks-menu">
                <span class="ks-pill" aria-hidden="true"></span>
                @can('backend.interact')
                    <li id="settings-sidenav-option" class="ks-item ks-item--parent {!! (request()->is(App\Helpers\Helper::SettingUrls()) ? ' active' : '') !!}">
                        <a class="ks-link" href="#" role="button" aria-expanded="false" id="settings">
                            <x-icon type="settings" class="fa-fw" />
                            <span class="ks-label">{{ trans('general.settings') }}</span>
                            <i class="fas fa-chevron-right ks-chevron" aria-hidden="true"></i>
                        </a>

                        <ul class="ks-sub">
                            @if(Gate::allows('view', App\Models\CustomField::class) || Gate::allows('view', App\Models\CustomFieldset::class))
                                <li style="--i:0;" {!! (request()->is('fields*') ? ' class="active" aria-current="page"' : '') !!}>
                                    <a href="{{ route('fields.index') }}">
                                        {{ trans('admin/custom_fields/general.custom_fields') }}
                                    </a>
                                </li>
                            @endif

                            @can('view', \App\Models\Statuslabel::class)
                                <li style="--i:1;" {!! (request()->is('statuslabels*') ? ' class="active" aria-current="page"' : '') !!}>
                                    <a href="{{ route('statuslabels.index') }}">
                                        {{ trans('general.status_labels') }}
                                    </a>
                                </li>
                            @endcan

                            @can('view', \App\Models\AssetModel::class)
                                <li style="--i:2;" {!! (request()->is('models*') ? ' class="active" aria-current="page"' : '') !!}>
                                    <a href="{{ route('models.index') }}">
                                        {{ trans('general.asset_models') }}
                                    </a>
                                </li>
                            @endcan

                            @can('view', \App\Models\Category::class)
                                <li style="--i:3;" {!! (request()->is('categories*') ? ' class="active" aria-current="page"' : '') !!}>
                                    <a href="{{ route('categories.index') }}">
                                        {{ trans('general.categories') }}
                                    </a>
                                </li>
                            @endcan

                            @can('view', \App\Models\Manufacturer::class)
                                <li style="--i:4;" {!! (request()->is('manufacturers*') ? ' class="active" aria-current="page"' : '') !!}>
                                    <a href="{{ route('manufacturers.index') }}">
                                        {{ trans('general.manufacturers') }}
                                    </a>
                                </li>
                            @endcan

                            @can('view', \App\Models\Supplier::class)
                                <li style="--i:5;" {!! (request()->is('suppliers*') ? ' class="active" aria-current="page"' : '') !!}>
                                    <a href="{{ route('suppliers.index') }}">
                                        {{ trans('general.suppliers') }}
                                    </a>
                                </li>
                            @endcan

                            @can('view', \App\Models\Department::class)
                                <li style="--i:6;" {!! (request()->is('departments*') ? ' class="active" aria-current="page"' : '') !!}>
                                    <a href="{{ route('departments.index') }}">
                                        {{ trans('general.departments') }}
                                    </a>
                                </li>
                            @endcan

                            @can('view', \App\Models\Location::class)
                                <li style="--i:7;" {!! (request()->is('locations*') ? ' class="active" aria-current="page"' : '') !!}>
                                    <a href="{{ route('locations.index') }}">
                                        {{ trans('general.locations') }}
                                    </a>
                                </li>
                            @endcan

                            @can('view', \App\Models\Company::class)
                                <li style="--i:8;" {!! (request()->is('companies*') ? ' class="active" aria-current="page"' : '') !!}>
                                    <a href="{{ route('companies.index') }}">
                                        {{ trans('general.companies') }}
                                    </a>
                                </li>
                            @endcan

                            @can('view', \App\Models\Depreciation::class)
                                <li style="--i:9;"  {!! (request()->is('depreciations*') ? ' class="active" aria-current="page"' : '') !!}>
                                    <a href="{{ route('depreciations.index') }}">
                                        {{ trans('general.depreciation') }}
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan

                @can('reports.view')
                    <li class="ks-item ks-item--parent{{ (request()->is('reports*') ? ' active' : '') }}">

                        <a class="ks-link" href="#" role="button" aria-expanded="false">
                            <x-icon type="reports" class="fa-fw" />
                            <span class="ks-label">{{ trans('general.reports') }}</span>
                            <i class="fas fa-chevron-right ks-chevron" aria-hidden="true"></i>
                        </a>

                        <ul class="ks-sub">
                            <li style="--i:0;" {!! (request()->is('reports') ? ' class="active" aria-current="page"' : '') !!}>
                                <a href="{{ route('reports.index') }}">
                                    {{ trans('general.list_all') }}
                                </a>
                            </li>
                            <li style="--i:1;" {!! (request()->is('reports/activity') ? ' class="active" aria-current="page"' : '') !!}>
                                <a href="{{ route('reports.activity') }}">
                                    {{ trans('general.activity_report') }}
                                </a>
                            </li>
                            <li style="--i:2;" {!! (request()->is('reports/custom') ? ' class="active" aria-current="page"' : '') !!}>
                                <a href="{{ url('reports/custom') }}">
                                    {{ trans('general.custom_report') }}
                                </a>
                            </li>
                            <li style="--i:3;" {!! (request()->routeIs('reports.custom.component') ? ' class="active" aria-current="page"' : '') !!}>
                                <a href="{{ route('reports.custom.component') }}">
                                    {{ trans('general.custom_component_report') }}
                                </a>
                            </li>
                            <li style="--i:4;" {!! (request()->routeIs('reports.custom.consumable') ? ' class="active" aria-current="page"' : '') !!}>
                                <a href="{{ route('reports.custom.consumable') }}">
                                    {{ trans('general.custom_consumable_report') }}
                                </a>
                            </li>
                            <li style="--i:5;" {!! (request()->is('reports/audit') ? ' class="active" aria-current="page"' : '') !!}>
                                <a href="{{ route('reports.audit') }}">
                                    {{ trans('general.audit_report') }}</a>
                            </li>
                            <li style="--i:6;" {!! (request()->is('reports/depreciation') ? ' class="active" aria-current="page"' : '') !!}>
                                <a href="{{ url('reports/depreciation') }}">
                                    {{ trans('general.depreciation_report') }}
                                </a>
                            </li>
                            <li style="--i:7;" {!! (request()->is('reports/licenses') ? ' class="active" aria-current="page"' : '') !!}>
                                <a href="{{ url('reports/licenses') }}">
                                    {{ trans('general.license_report') }}
                                </a>
                            </li>
                            <li style="--i:8;" {!! (request()->routeIs('ui.reports.maintenances') ? ' class="active" aria-current="page"' : '') !!}>
                                <a href="{{ route('ui.reports.maintenances') }}">
                                    {{ trans('general.asset_maintenance_report') }}
                                </a>
                            </li>
                            <li style="--i:9;" {!! (request()->is('reports/unaccepted_assets') ? ' class="active" aria-current="page"' : '') !!}>
                                <a href="{{ url('reports/unaccepted_assets') }}">
                                    {{ trans('general.unaccepted_asset_report') }}
                                </a>
                            </li>
                            <li style="--i:10;"  {!! (request()->is('reports/accessories') ? ' class="active" aria-current="page"' : '') !!}>
                                <a href="{{ url('reports/accessories') }}">
                                    {{ trans('general.accessory_report') }}
                                </a>
                            </li>
                        </ul>
                    </li>
                @endcan
                    @can('canCheckoutAtLeastOneItemType')
                        <li class="ks-item"{!! (request()->is('requests.index') ? ' active" aria-current="page"' : '') !!}>
                            <a class="ks-link" href="{{ route('requests.index') }}">
                                <i class="fa-solid fa-boxes-packing"></i>
                                <span class="ks-label">{{ trans('general.pending_requests') }}</span>
                            </a>
                        </li>
                    @endcan

                @can('viewRequestable', \App\Models\Asset::class)
                    <li class="ks-item"{!! (request()->is('account/requestable') ? ' active" aria-current="page"' : '') !!}>
                        <a class="ks-link" href="{{ route('account.requestable') }}">
                            <x-icon type="requestable" class="fa-fw" />
                            <span class="ks-label">{{ trans('general.requestable_items') }}</span>
                        </a>
                    </li>
                @endcan
            </ul>
        </div>

    </nav>

    <button type="button" class="ks-rail-toggle" id="ks-rail-toggle"
            aria-label="{{ trans('general.toggle_navigation') }}">
        <x-icon type="angle-left" class="fa-fw" />
    </button>
</aside>
<div class="ks-scrim" aria-hidden="true"></div>
{{-- [/killa-v2 sidebar] --}}
