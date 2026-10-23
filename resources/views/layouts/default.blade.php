<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ Helper::determineLanguageDirection() }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>
        @section('title')
        @show
        :: {{ $snipeSettings->site_name }}
    </title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1" name="viewport">

    <meta name="apple-mobile-web-app-capable" content="yes">


    <link rel="apple-touch-icon"
          href="{{ ($snipeSettings) && ($snipeSettings->favicon!='') ?  Storage::disk('public')->url(e($snipeSettings->logo)) :  config('app.url').'/img/snipe-logo-bug.png' }}">
    <link rel="apple-touch-startup-image"
          href="{{ ($snipeSettings) && ($snipeSettings->favicon!='') ?  Storage::disk('public')->url(e($snipeSettings->logo)) :  config('app.url').'/img/snipe-logo-bug.png' }}">
    <link rel="shortcut icon" type="image/ico"
          href="{{ ($snipeSettings) && ($snipeSettings->favicon!='') ?  Storage::disk('public')->url(e($snipeSettings->favicon)) : config('app.url').'/favicon.ico' }}">


    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="language" content="{{ Helper::mapBackToLegacyLocale(app()->getLocale()) }}">
    <meta name="language-direction" content="{{ Helper::determineLanguageDirection() }}">
    <meta name="baseUrl" content="{{ config('app.url') }}/">
    <meta name="theme-color" content="{{ $snipeSettings->header_color ?? '#5fa4cc' }}">

    <script nonce="{{ csrf_token() }}">
        window.Laravel = {csrfToken: '{{ csrf_token() }}'};
    </script>

    @include('partials.theme-mode-preflight')

    {{-- stylesheets --}}
    <link rel="stylesheet" href="{{ url(mix('css/dist/all.css')) }}">
    {{-- [killa-v2] fonts: self-hosted Inter (UI) + Inter Tight (display), no Google CDN (UI-A-08) --}}
    <link rel="stylesheet" href="{{ url('css/killa-v2-fonts.css') }}?v={{ config('version.app_version') }}">
    {{-- [killa-v2] theme layer: plain committed stylesheets, no build step. Load after all.css so they win the cascade; tenant branding vars below still override brand colors. Cache-busted on app version (changes each release), not per-request md5_file(). --}}
    <link rel="stylesheet" href="{{ url('css/killa-v2.css') }}?v={{ config('version.app_version') }}">
    {{-- [killa-v2 sidebar] animated sidebar rail overlay (KCP-049) --}}
    <link rel="stylesheet" href="{{ url('css/killa-v2-sidebar.css') }}?v={{ config('version.app_version') }}">
    <link rel="stylesheet" href="{{ url('css/killa-v2-forms.css') }}?v={{ config('version.app_version') }}">
    <link rel="stylesheet" href="{{ url('css/killa-v2-tables.css') }}?v={{ config('version.app_version') }}">
    <link rel="stylesheet" href="{{ url('css/killa-v2-widgets.css') }}?v={{ config('version.app_version') }}">
    <link rel="stylesheet" href="{{ url('css/killa-v2-overlays.css') }}?v={{ config('version.app_version') }}">
    {{-- [killa-ux] Licenses redesign design system (KCP-048): self-contained layer, only active inside .kx wrapper containers. --}}
    <link rel="stylesheet" href="{{ url('css/killa-ux.css') }}?v={{ config('version.app_version') }}">
    <link rel="stylesheet" href="{{ url('css/killa-v2-dark.css') }}?v={{ config('version.app_version') }}">

    {{-- page level css --}}
    @stack('css')


    @include('partials.theme-mode-tenant-vars')

    {{-- Custom CSS --}}
    @if (($snipeSettings) && ($snipeSettings->custom_css))
        <style>
            {!! $snipeSettings->show_custom_css() !!}
        </style>
    @endif

    {{-- [killa-v2] Notification navigation: load after tenant custom CSS so inaccessible contrast overrides cannot win. --}}
    <link rel="stylesheet" href="{{ url('css/killa-v2-notifications.css') }}?v={{ config('version.app_version') }}">

    <script nonce="{{ csrf_token() }}">
        window.snipeit = {
            settings: {
                "per_page": {{ $snipeSettings->per_page }},
                "first_day_of_week": {{ (int) $snipeSettings->week_start }}
            }
        };
    </script>

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <script src="{{ url(asset('js/html5shiv.js')) }}" nonce="{{ csrf_token() }}"></script>
    <script src="{{ url(asset('js/respond.js')) }}" nonce="{{ csrf_token() }}"></script>

    {{-- [killa-v2 sidebar] pre-paint collapse restore: marks <html> before
         first paint so the rail never flashes expanded; killa-sidebar.js
         moves the state onto <body> (KCP-049) --}}
    <script nonce="{{ csrf_token() }}">
        try {
            if (localStorage.getItem('ks.sidebar') === 'collapsed') {
                document.documentElement.classList.add('ks-collapsed-pre');
            }
        } catch (e) {}
    </script>

</head>

    <body class="sidebar-mini{{ (session('menu_state')!='open') ? ' sidebar-mini sidebar-collapse' : ''  }}">

        <a class="skip-main" href="#main">{{ trans('general.skip_to_main_content') }}</a>
        <div class="wrapper">

            <header class="main-header">

                <!-- Logo -->

                <!-- Header Navbar: style can be found in header.less -->
                <nav class="navbar navbar-static-top" role="navigation">
                    <!-- Sidebar toggle button above the compact sidenav -->
                    <a href="#" style="color: white" class="sidebar-toggle btn btn-white" data-toggle="push-menu"
                       role="button">
                        <span class="sr-only">{{ trans('general.toggle_navigation') }}</span>
                    </a>
                    <div class="nav navbar-nav navbar-left">
                        <div class="left-navblock">
                            @if ($snipeSettings->brand == '3')
                                <a class="logo navbar-brand no-hover" href="{{ config('app.url') }}">
                                    @if ($snipeSettings->logo!='')
                                        <img class="navbar-brand-img"
                                             src="{{ Storage::disk('public')->url($snipeSettings->logo) }}"
                                             alt="{{ $snipeSettings->site_name }} logo">
                                    @endif
                                    {{ $snipeSettings->site_name }}
                                </a>
                            @elseif ($snipeSettings->brand == '2')
                                <a class="logo navbar-brand no-hover" href="{{ config('app.url') }}">
                                    @if ($snipeSettings->logo!='')
                                        <img class="navbar-brand-img"
                                             src="{{ Storage::disk('public')->url($snipeSettings->logo) }}"
                                             alt="{{ $snipeSettings->site_name }} logo">
                                    @endif
                                    <span class="sr-only">{{ $snipeSettings->site_name }}</span>
                                </a>
                            @else
                                <a class="logo navbar-brand no-hover" href="{{ config('app.url') }}">
                                    {{ $snipeSettings->site_name }}
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Navbar Right Menu -->
                    <div class="navbar-custom-menu">
                        <ul class="nav navbar-nav">
                            <li aria-hidden="true">

                                    <a href="#" class="sidebar-toggle-mobile visible-xs hidden-lg hidden-md" data-toggle="push-menu"
                                   role="button">
                                    <span class="sr-only">{{ trans('general.toggle_navigation') }}</span>
                                    <x-icon type="nav-toggle" />
                                </a>

                            </li>

                            @can('index', \App\Models\Asset::class)
                                <li aria-hidden="true"{!! (request()->is('hardware*') ? ' class="active" aria-current="page"' : '') !!}>
                                    <a href="{{ url('hardware') }}" {{$snipeSettings->shortcuts_enabled == 1 ? "accesskey=1" : ''}} tabindex="-1" data-tooltip="true" data-placement="bottom" data-title="{{ trans('general.assets') }}">
                                        <x-icon type="assets" class="fa-fw" />
                                        <span class="sr-only">{{ trans('general.assets') }}</span>
                                    </a>
                                </li>
                            @endcan
                            @can('view', \App\Models\License::class)
                                <li aria-hidden="true"{!! (request()->is('licenses*') ? ' class="active" aria-current="page"' : '') !!}>
                                    <a href="{{ route('licenses.index') }}" {{$snipeSettings->shortcuts_enabled == 1 ? "accesskey=2" : ''}} tabindex="-1" data-tooltip="true" data-placement="bottom" data-title="{{ trans('general.licenses') }}">
                                        <x-icon type="licenses" class="fa-fw" />
                                        <span class="sr-only">{{ trans('general.licenses') }}</span>
                                    </a>
                                </li>
                            @endcan
                            @can('index', \App\Models\Accessory::class)
                                <li aria-hidden="true"{!! (request()->is('accessories*') ? ' class="active" aria-current="page"' : '') !!}>
                                    <a href="{{ route('accessories.index') }}" {{$snipeSettings->shortcuts_enabled == 1 ? "accesskey=3" : ''}} tabindex="-1" data-tooltip="true" data-placement="bottom" data-title="{{ trans('general.accessories') }}">
                                        <x-icon type="accessories" class="fa-fw" />
                                        <span class="sr-only">{{ trans('general.accessories') }}</span>
                                    </a>
                                </li>
                            @endcan
                            @can('index', \App\Models\Consumable::class)
                                <li aria-hidden="true"{!! (request()->is('consumables*') ? ' class="active" aria-current="page"' : '') !!}>
                                    <a href="{{ url('consumables') }}" {{$snipeSettings->shortcuts_enabled == 1 ? "accesskey=4" : ''}} tabindex="-1" data-tooltip="true" data-placement="bottom" data-title="{{ trans('general.consumables') }}">
                                        <x-icon type="consumables" class="fa-fw" />
                                        <span class="sr-only">{{ trans('general.consumables') }}</span>
                                    </a>
                                </li>
                            @endcan
                            @can('view', \App\Models\Component::class)
                                <li aria-hidden="true"{!! (request()->is('components*') ? ' class="active" aria-current="page"' : '') !!}>
                                    <a href="{{ route('components.index') }}" {{$snipeSettings->shortcuts_enabled == 1 ? "accesskey=5" : ''}} tabindex="-1" data-tooltip="true" data-placement="bottom" data-title="{{ trans('general.components') }}">
                                        <x-icon type="components" class="fa-fw" />
                                        <span class="sr-only">{{ trans('general.components') }}</span>
                                    </a>
                                </li>
                            @endcan

                            @can('index', \App\Models\User::class)
                                <li aria-hidden="true"{!! (request()->is('users*') ? ' class="active" aria-current="page"' : '') !!}>
                                    <a href="{{ route('users.index') }}" {{$snipeSettings->shortcuts_enabled == 1 ? "accesskey=6" : ''}} tabindex="-1" data-tooltip="true" data-placement="bottom" data-title="{{ trans('general.users') }}">
                                        <x-icon type="users" class="fa-fw" />
                                        <span class="sr-only">{{ trans('general.users') }}</span>
                                    </a>
                                </li>
                            @endcan

                            @can('index', \App\Models\Asset::class)
                                <li>
                                    <form class="navbar-form navbar-left form-inline" role="search" action="{{ route('findbytag/hardware') }}" method="get">

                                                <div class="input-group col-xs-12" style="border: 0 !important;">
                                                    <label class="sr-only" for="tagSearch">
                                                        {{ trans('general.lookup_by_tag') }}
                                                    </label>
                                                    <input type="text" class="form-control" id="tagSearch" name="assetTag" placeholder="{{ trans('general.lookup_by_tag') }}">
                                                    <span class="input-group-btn">
                                                        <button type="submit" id="topSearchButton" class="btn btn-sm btn-theme" style="padding: 7px 10px 7px 10px; "><x-icon type="search" class="fa-fw" /><div class="sr-only">{{ trans('general.search') }}</div></button>
                                                    </span>
                                                </div>

                                        <input type="hidden" name="topsearch" value="true" id="search">

                                    </form>
                                </li>
                            @endcan

                            @can('admin')
                                <li class="dropdown user-menu" aria-hidden="true">
                                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" tabindex="-1" aria-haspopup="true" aria-expanded="false">
                                        {{ trans('general.create') }}
                                        <strong class="caret"></strong>
                                    </a>
                                    <ul class="dropdown-menu">
                                        @can('create', \App\Models\Asset::class)
                                            <li{!! (request()->is('hardware/create') ? ' class="active" aria-current="page"' : '') !!}>
                                                <a href="{{ route('hardware.create') }}" tabindex="-1">
                                                    <x-icon type="assets" class="fa-fw" />
                                                    {{ trans('general.asset') }}
                                                </a>
                                            </li>
                                        @endcan
                                        @can('create', \App\Models\License::class)
                                            <li{!! (request()->is('licenses/create') ? ' class="active" aria-current="page"' : '') !!}>
                                                <a href="{{ route('licenses.create') }}" tabindex="-1">
                                                    <x-icon type="licenses" class="fa-fw" />
                                                    {{ trans('general.license') }}
                                                </a>
                                            </li>
                                        @endcan
                                        @can('create', \App\Models\Accessory::class)
                                            <li {!! (request()->is('accessories/create') ? 'class="active"' : '') !!}>
                                                <a href="{{ route('accessories.create') }}" tabindex="-1">
                                                    <x-icon type="accessories" class="fa-fw" />
                                                    {{ trans('general.accessory') }}
                                                </a>
                                            </li>
                                        @endcan
                                        @can('create', \App\Models\Consumable::class)
                                            <li {!! (request()->is('consunmables/create') ? 'class="active"' : '') !!}>
                                                <a href="{{ route('consumables.create') }}" tabindex="-1">
                                                    <x-icon type="consumables" class="fa-fw" />
                                                    {{ trans('general.consumable') }}
                                                </a>
                                            </li>
                                        @endcan
                                        @can('create', \App\Models\Component::class)
                                            <li {!! (request()->is('components/create') ? 'class="active"' : '') !!}>
                                                <a href="{{ route('components.create') }}" tabindex="-1">
                                                    <x-icon type="components" class="fa-fw" />
                                                    {{ trans('general.component') }}
                                                </a>
                                            </li>
                                        @endcan
                                        @can('create', \App\Models\User::class)
                                            <li {!! (request()->is('users/create') ? 'class="active"' : '') !!}>
                                                <a href="{{ route('users.create') }}" tabindex="-1">
                                                    <x-icon type="users" class="fa-fw" />
                                                    {{ trans('general.user') }}
                                                </a>
                                            </li>
                                        @endcan


                                    </ul>
                                </li>
                            @endcan

                            @can('admin')
                                @if ($snipeSettings->show_alerts_in_menu == '1')
                                    <livewire:alert-menu/>
                                @endif
                            @endcan



                            <!-- User Account: style can be found in dropdown.less -->
                            @auth
                                <li class="dropdown user user-menu">

                                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        @if (auth()->user()->present()->gravatar())
                                            <img src="{{ Auth::user()->present()->gravatar() }}" class="user-image"
                                                 referrerpolicy="no-referrer"
                                                 alt="">
                                        @else
                                            <x-icon type="user" />
                                        @endif

                                        <span class="hidden-xs">
                                            {{ Auth::user()->display_name }}
                                            <strong class="caret"></strong>
                                        </span>
                                    </a>


                                    <ul class="dropdown-menu">

                                        <!-- User assets -->
                                        @can('self.profile')
                                        <li {!! (request()->is('account/view-assets') ? ' class="active" aria-current="page"' : '') !!}>
                                            <a href="{{ route('view-assets') }}">
                                                <x-icon type="checkmark" class="fa-fw" />
                                                {{ trans('general.viewassets') }}
                                            </a>
                                        </li>
                                        @endcan


                                        @can('viewRequestable', \App\Models\Asset::class)
                                            <li {!! (request()->is('account/requested') ? ' class="active" aria-current="page"' : '') !!}>
                                                <a href="{{ route('account.requested') }}">
                                                    <x-icon type="requested" class="fa-fw" />
                                                    {{ trans('general.requested_assets_menu') }}
                                                </a></li>
                                        @endcan

                                        @can('self.profile')
                                        <li {!! (request()->is('account/accept') ? ' class="active" aria-current="page"' : '') !!}>
                                            <a href="{{ route('account.accept') }}">
                                                <x-icon type="signature" class="fa-fw"/>
                                                {{ trans('general.accept_assets_menu') }}
                                            </a>
                                        </li>

                                        @endcan
                                        <li {!! (request()->is('account/profile') ? ' class="active" aria-current="page"' : '') !!}>
                                            <a href="{{ route('profile') }}">
                                                <x-icon type="user" class="fa-fw" />
                                                {{ trans('general.editprofile') }}
                                            </a>
                                        </li>

                                        @can('self.profile')
                                            @if (Auth::user()->ldap_import!='1')
                                                <li {!! (request()->is('account/password') ? ' class="active" aria-current="page"' : '') !!}>
                                                    <a href="{{ route('account.password.index') }}">
                                                        <x-icon type="password" class="fa-fw"/>
                                                        {{ trans('general.changepassword') }}
                                                    </a>
                                                </li>
                                            @endif
                                        @endcan

                                        <li>
                                            <a type="button" data-theme-toggle aria-label="{{ trans('general.dark_mode') }}" class="btn-link btn-anchor" onclick="event.preventDefault();">
                                                {{ trans('general.dark_mode') }}
                                            </a>
                                        </li>

                                        @can('self.api')
                                            <li {!! (request()->is('account/api') ? ' class="active" aria-current="page"' : '') !!}>
                                                <a href="{{ route('user.api') }}">
                                                    <x-icon type="api-key" class="fa-fw" />
                                                     {{ trans('general.manage_api_keys') }}
                                                </a>
                                            </li>
                                        @endcan
                                        
                                        <li class="divider"></li>
                                        <li>
                                            <a href="{{ route('logout.get') }}"
                                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                                <x-icon type="logout" class="fa-fw" />
                                                 {{ trans('general.logout') }}
                                            </a>

                                            <form id="logout-form" action="{{ route('logout.post') }}" method="POST" style="display: none;">
                                                <button type="submit" style="display: none;" title="logout"></button>
                                                {{ csrf_field() }}
                                            </form>

                                        </li>
                                    </ul>
                                </li>
                            @endauth


                            @can('superadmin')
                                <li>
                                    <a href="{{ route('settings.index') }}">
                                        <x-icon type="admin-settings" />
                                        <span class="sr-only">{{ trans('general.admin') }}</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </div>
                </nav>

                <!-- Sidebar toggle button-->
            </header>
            {{-- [killa-v2 sidebar] upstream <aside class="main-sidebar"> block replaced by a Killa-owned partial (KCP-049) --}}
            @include('layouts.partials.killa-sidebar')
            {{-- [/killa-v2 sidebar] --}}
                <!-- /.sidebar -->
            </aside>

            <!-- Content Wrapper. Contains page content -->

            <div class="content-wrapper" role="main" id="setting-list">

                @include('partials.impersonation-banner')

                @if ($debug_in_production)
                    <div class="row" style="margin-bottom: 0px; background-color: red; color: white; font-size: 15px;">
                        <div class="col-md-12"
                             style="margin-bottom: 0px; background-color: #b50408 ; color: white; padding: 10px 20px 10px 30px; font-size: 16px;">
                            <x-icon type="warning" class="fa-3x pull-left"/>
                            <strong>{{ strtoupper(trans('general.debug_warning')) }}:</strong>
                            {!! trans('general.debug_warning_text') !!}
                        </div>
                    </div>
                @endif

                <!-- Content Header (Page header) -->
                <section class="content-header">


                    <div class="row">
                        <div class="col-md-12" style="margin-bottom: 0px;">

                        <style>
                            .breadcrumb-item {
                                display: inline;
                                list-style: none;
                            }
                        </style>

                            <h1 class="pull-left pagetitle" style="font-size: 22px; margin-top: 5px;">

                                @if (Breadcrumbs::has() && (Breadcrumbs::current()->count() > 1))
                                    <ul style="padding-left: 0;">

                                    @foreach (Breadcrumbs::current() as $crumbs)
                                        @if ($crumbs->url() && !$loop->last)
                                            <li class="breadcrumb-item">
                                                <a href="{{ $crumbs->url() }}">
                                                    @if ($loop->first)
                                                        <x-icon type="home" />
                                                    @else
                                                        {{ $crumbs->title() }}
                                                    @endif
                                                </a>
                                                <x-icon type="angle-right" />
                                            </li>
                                        @elseif (is_null($crumbs->url()) && !$loop->last)
                                            <li class="breadcrumb-item active">
                                                {{ $crumbs->title() }}
                                                <x-icon type="angle-right" />
                                            </li>
                                       @else
                                            <li class="breadcrumb-item active">
                                                {{ $crumbs->title() }}
                                            </li>
                                        @endif
                                    @endforeach

                                    </ul>
                                @else
                                    @yield('title')
                                @endif

                            </h1>

                                @if (isset($helpText))
                                    @include ('partials.more-info',
                                                           [
                                                               'helpText' => $helpText,
                                                               'helpPosition' => (isset($helpPosition)) ? $helpPosition : 'left'
                                                           ])
                                @endif
                                <div class="pull-right">
                                    @yield('header_right')
                                </div>

                        </div>
                    </div>
                </section>


                <section class="content" id="main" tabindex="-1" style="padding-top: 0px;">

                    <!-- Notifications -->
                    <div class="row">
                        @if (config('app.lock_passwords'))
                            <div class="col-md-12">
                                <x-callout type="info" role="status">
                                    {{ trans('general.some_features_disabled') }}
                                </x-callout>
                            </div>
                        @endif

                        <x-notifications />
                    </div>


                    <!-- Content -->
                    <div id="{!! (request()->is('*api*') ? 'app' : 'webui') !!}">
                        @yield('content')
                    </div>

                    {{-- [killa-ux] old/new design switch pill on licenses pages (KCP-052) --}}
                    @if (request()->is('licenses*') && auth()->check() && auth()->user()->can('view', \App\Models\License::class))
                        @include('killa/licenses/_ux-toggle')
                    @endif

                </section>

            </div><!-- /.content-wrapper -->
            <footer class="main-footer hidden-print" style="display:grid;flex-direction:column;">

                <div class="hidden-xs pull-left">
                    <div class="pull-left footer-links">
                         {!! trans('general.footer_credit') !!}
                    </div>
                    <div class="pull-right">
                    @if ($snipeSettings->version_footer!='off')
                        @if (($snipeSettings->version_footer=='on') || (($snipeSettings->version_footer=='admin') && (Auth::user()->isSuperUser()=='1')))
                            &nbsp; {{ trans('general.version') }} {{ config('version.app_version') }} -
                            {{ trans('general.build') }} {{ config('version.build_version') }} ({{ config('version.branch') }})
                        @endif
                    @endif

                    @if (isset($user) && ($user->isSuperUser()) && (app()->environment('local')))
                       <a href="{{ url('telescope') }}" class="label label-default" rel="noopener">Open Telescope</a>
                    @endif




                    @if ($snipeSettings->support_footer!='off')
                        @if (($snipeSettings->support_footer=='on') || (($snipeSettings->support_footer=='admin') && (Auth::user()->isSuperUser()=='1')))
                            <a target="_blank" class="label label-default"
                               href="https://snipe-it.readme.io/docs/overview"
                               rel="noopener">{{ trans('general.user_manual') }}</a>
                        @endif
                    @endif

                    @if ($snipeSettings->privacy_policy_link!='')
                        <a target="_blank" class="label label-default" rel="noopener"
                           href="{{  $snipeSettings->privacy_policy_link }}"
                           target="_new">{{ trans('admin/settings/general.privacy_policy') }}</a>
                    @endif
                    </div>
                    <br>
                    @if ($snipeSettings->footer_text!='')
                        <div class="pull-left">
                            {!!  Helper::parseEscapedMarkedown($snipeSettings->footer_text)  !!}
                        </div>
                    @endif
                </div>
            </footer>
        </div><!-- ./wrapper -->


        <!-- end main container -->

        <div class="modal modal-danger fade" id="dataConfirmModal" tabindex="-1" role="dialog" aria-labelledby="dataConfirmModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title" id="dataConfirmModalLabel">
                            <span class="modal-header-icon"></span>&nbsp;
                        </h4>
                    </div>
                    <div class="modal-body"></div>
                    <div class="modal-footer">
                        <form method="post" id="deleteForm" role="form" action="">
                            {{ csrf_field() }}
                            {{ method_field('DELETE') }}

                            <button type="button" class="btn btn-default pull-left"
                                    data-dismiss="modal">{{ trans('general.cancel') }}</button>
                            <button type="submit" class="btn btn-outline"
                                    id="dataConfirmOK">{{ trans('general.yes') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>


        <div class="modal modal-warning fade" id="restoreConfirmModal" tabindex="-1" role="dialog"
             aria-labelledby="confirmModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title" id="confirmModalLabel">&nbsp;</h4>
                    </div>
                    <div class="modal-body"></div>
                    <div class="modal-footer">
                        <form method="post" id="restoreForm" role="form">
                            {{ csrf_field() }}
                            {{ method_field('POST') }}

                            <button type="button" class="btn btn-default pull-left"
                                    data-dismiss="modal">{{ trans('general.cancel') }}</button>
                            <button type="submit" class="btn btn-outline"
                                    id="dataConfirmOK">{{ trans('general.yes') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>



        {{-- Javascript files --}}
        <script src="{{ url(mix('js/dist/all.js')) }}" nonce="{{ csrf_token() }}"></script>
        <script src="{{ url('js/select2/i18n/'.Helper::mapBackToLegacyLocale(app()->getLocale()).'.js') }}"></script>

        {{-- [killa-v2 sidebar] vanilla sidebar controller (KCP-049) --}}
        <script src="{{ url(asset('js/killa-sidebar.js')) }}?v={{ config('version.app_version') }}" defer nonce="{{ csrf_token() }}"></script>

        {{-- Page level javascript --}}
        @stack('js')

        @section('moar_scripts')
        @show

        <script nonce="{{ csrf_token() }}">

            // Handle the first selected tabs regardless of permissions
            if ($('li.snipetab').is(':first-of-type')) {
                var hash = $('li.snipetab:first-of-type').children().attr('href');
                $('li.snipetab:first-of-type').addClass('active');
                $('div'+hash+'.snipetab-pane').addClass('in active');
            }


            //color picker with addon
            $(".color").colorpicker();


            var clipboard = new ClipboardJS('.js-copy-link');

            clipboard.on('success', function(e) {
                e.text = e.text.replace(/^\s/, '').trim();
                var clickedElement = $(e.trigger);
                clickedElement.tooltip('hide').attr('data-original-title', '{{ trans('general.copied') }}').tooltip('show');
            });


            // Reference: https://jqueryvalidation.org/validate/
            //
            // A handful of form-ids get the same validator: `create-form` is
            // the default id emitted by the form blade component, `checkout_form`
            // is the anti-double-submit id used by the six checkout flows, and
            // the sync-adapter tabs each render their own form as
            // `adapter-form-<slug>`. Everyone needs the same error styling +
            // select2 error placement, so we init in a loop instead of
            // duplicating the options block.
            var snipeValidatorOptions = {
                ignore: 'input[type=hidden]',
                errorClass: 'alert-msg',
                errorElement: 'div',
                errorPlacement: function(error, element) {
                    // Screen readers only announce inserted error text when the
                    // error element carries role=alert (aria-live=assertive is
                    // implied, but set explicitly for older AT compatibility).
                    // The X icon is applied via the .alert-msg::before CSS rule
                    // (see resources/assets/less/app.less); prepending it here
                    // would be wiped on re-validation, when jQuery Validate
                    // calls label.html(msg) on the existing element.
                    error.attr('role', 'alert');
                    error.attr('aria-live', 'assertive');

                    if ($(element).hasClass('select2') || $(element).hasClass('js-data-ajax')) {
                        // If the element is a select2 then append the error to the parent div
                        element.parent('div').append(error);

                     } else if ($(element).parent().hasClass('input-group')) {
                        var end_input_group = $(element).next('.input-group-addon').parent();
                        error.insertAfter(end_input_group);
                    } else {
                        error.insertAfter(element);
                    }

                },
                highlight: function(inputElement) {
                    // Put the error-state class on the enclosing .form-group
                    // so both the input AND its <label class="control-label">
                    // get Bootstrap 3's has-error decoration (the label goes
                    // red via .has-error .control-label). .closest() walks up
                    // regardless of nesting, so this works for plain inputs,
                    // input-groups (input + addon), and select2-wrapped selects
                    // without a per-shape branch.
                    var $group = $(inputElement).closest('.form-group');
                    $group.addClass('has-error');
                    // Blow away any inline help block that would collide with
                    // the newly-inserted error text.
                    $group.find('.help-block').remove();
                },
                unhighlight: function(inputElement) {
                    $(inputElement).closest('.form-group').removeClass('has-error');
                },

            };

            $('#create-form, #checkout_form, #userForm, #adjustQuantityForm, form[id^="adapter-form-"]').each(function () {
                $(this).validate(snipeValidatorOptions);
            });

            $.extend($.validator.messages, {
                required: "{{ trans('validation.generic.required') }}",
                email: "{{ trans('validation.generic.email') }}"
            });

            $.validator.addMethod('pattern', function(value, element, param) {
                if (this.optional(element)) {
                    return true;
                }
                if (typeof param === 'string') {
                    param = new RegExp('^(?:' + param + ')$');
                }
                return param.test(value);
            }, '{{ trans('validation.generic.invalid_value_in_field') }}');

            // Generic radio-toggles-required-select handler. Any form pattern
            // where a radio group hides/shows sibling <select>s (checkout-to
            // type in checkout forms today; any future similar toggle) can
            // opt in by giving the radios a `data-required-select` attribute
            // whose value is a CSS selector for the field the checked radio
            // should mark required. Radios that don't set the attribute are
            // ignored; selects that only appear as data-required-select
            // targets get their required attribute cleared when a different
            // radio in the same group is chosen. Runs once on ready + on
            // every change so page-refresh state and interactive toggles
            // both stay in sync.
            //
            // The visibility check is critical for asset create/edit, where
            // the checkout-selector partial is rendered hidden and only
            // revealed after a deployable status is picked. Without it, the
            // browser tries to enforce required on an invisible select and
            // silently blocks the save.
            var applyRadioRequiredSelects = function () {
                // Group opted-in radios by name, collecting the list of CSS
                // selectors each group can point at. Reading via .attr()
                // rather than .data() because .data() caches on first access
                // and can miss late-changing values in select2 / Bootstrap
                // data-toggle="buttons" environments. Only VISIBLE radios
                // count — a hidden checkout-selector on asset create/edit
                // is inert and shouldn't be pinning required on anything.
                var groups = {};
                $('input[type=radio][data-required-select]:visible').each(function () {
                    var name = this.name;
                    var target = this.getAttribute('data-required-select');
                    if (!name || !target) return;
                    if (!groups[name]) groups[name] = [];
                    if (groups[name].indexOf(target) === -1) groups[name].push(target);
                });

                // For each group, pin required on the currently-checked
                // radio's target and clear it from every sibling target.
                // If the target select itself is hidden (e.g., the "user"
                // form-group is display:none because a non-deployable status
                // was picked), don't set required on it — the browser would
                // block form submit on an invisible element.
                Object.keys(groups).forEach(function (name) {
                    var $checked = $('input[name="' + name + '"]:checked');
                    var checkedTarget = $checked.length ? $checked[0].getAttribute('data-required-select') : null;
                    groups[name].forEach(function (selector) {
                        var $target = $(selector);
                        var shouldBeRequired = selector === checkedTarget && $target.is(':visible');
                        $target.prop('required', shouldBeRequired);
                    });
                });
            };
            $(document).on('change', 'input[type=radio][data-required-select]', applyRadioRequiredSelects);
            $(document).ready(applyRadioRequiredSelects);


            function showHideEncValue(e) {
                // Use element id to find the text element to hide / show
                var targetElement = e.id+"-to-show";
                var hiddenElement = e.id+"-to-hide";
                var targetEl = document.getElementById(targetElement);
                var isMarkdown = targetEl && targetEl.dataset.markdown;
                var audio = new Audio('{{ config('app.url') }}/sounds/lock.mp3');
                if($(e).hasClass('fa-lock')) {
                    @if ((isset($user)) && ($user->enable_sounds))
                        audio.play()
                    @endif
                    $(e).removeClass('fa-lock').addClass('fa-unlock');
                    // Show the encrypted custom value and hide the element with asterisks
                    if (isMarkdown) {
                        targetEl.style.display = "block";
                    } else {
                        targetEl.style.fontSize = "100%";
                    }
                    document.getElementById(hiddenElement).style.display = "none";

                } else {
                    @if ((isset($user)) && ($user->enable_sounds))
                        audio.play()
                    @endif
                    $(e).removeClass('fa-unlock').addClass('fa-lock');
                    // ClipboardJS can't copy display:none elements so use a trick to hide the value
                    if (isMarkdown) {
                        targetEl.style.display = "none";
                    } else {
                        // ClipboardJS can't copy display:none elements so use a trick to hide the value
                        targetEl.style.fontSize = "0px";
                    }
                    document.getElementById(hiddenElement).style.display = "";

                 }
             }




            function checkInfoSidePanel() {
                var side_panel_state = localStorage.getItem("side_panel_state");

                // Open side info panel
                if (side_panel_state == 'collapsed') {
                    collapseInfoSidePanel();

                // Collapse side info panel
                } else {
                    expandInfoSidePanel();
                }

            }

            function toggleInfoSidePanel() {
                var side_panel_state = localStorage.getItem("side_panel_state");

                if (side_panel_state == 'expanded') {
                    localStorage.setItem("side_panel_state", 'collapsed');
                } else {
                    localStorage.setItem("side_panel_state", 'expanded');
                }

                checkInfoSidePanel();
            }

            function collapseInfoSidePanel() {
                $('.side-box').removeClass('expanded').hide();
                $('.main-panel').removeClass('col-md-9').addClass('col-md-12');
                $("#expand-info-panel-button").addClass('fa-square-caret-left').removeClass('fa-square-caret-right');
            }

            function expandInfoSidePanel() {
                $('.side-box').fadeIn("fast").addClass('expanded');
                $('.main-panel').removeClass('col-md-12').addClass('col-md-9');
                $("#expand-info-panel-button").addClass('fa-square-caret-right').removeClass('fa-square-caret-left');
            }


            $(document).ready(function () {
                checkInfoSidePanel();

                // Handle the info-panel
                $("#expand-info-panel-button").click(function () {
                    toggleInfoSidePanel();
                });



                // This handles the show/hide for cloned items
                $('#use_cloned_image').click(function() {
                    if ($('#use_cloned_image').is(':checked')) {
                        $('#image_delete').prop('checked', false);
                        $('#image-upload').hide();
                        $('#existing-image').show();
                    } else {
                        $('#image-upload').show();
                        $('#existing-image').hide();
                    }
                    //$('#image-upload').hide();
                });

                // Invoke Bootstrap 3's tooltip
                $('[data-tooltip="true"]').tooltip({
                    container: 'body',
                    animation: true,
                });

                $('[data-toggle="popover"]').popover();
                $('.select2 span').addClass('needsclick');
                $('.select2 span').removeAttr('title');

                // This javascript handles saving the state of the menu (expanded or not)
                $('body').bind('expanded.pushMenu', function () {
                    $.ajax({
                        type: 'GET',
                        url: "{{ route('account.menuprefs', ['state'=>'open']) }}",
                        _token: "{{ csrf_token() }}"
                    });

                });

                $('body').bind('collapsed.pushMenu', function () {
                    $.ajax({
                        type: 'GET',
                        url: "{{ route('account.menuprefs', ['state'=>'close']) }}",
                        _token: "{{ csrf_token() }}"
                    });
                });

            });

            // Initiate the ekko lightbox
            $(document).on('click', '[data-toggle="lightbox"]', function (event) {
                event.preventDefault();
                $(this).ekkoLightbox();
            });
            // Anti-double-click on checkout forms. The old implementation did
            //   event.preventDefault(); $btn.prop('disabled', true); this.submit();
            // which submitted the form NATIVELY (without re-firing the submit
            // event), bypassing jQuery Validate entirely — hence any JS
            // validation error was visible for a single frame before the form
            // shipped straight to the server.
            //
            // New shape: let the normal submit lifecycle run, and only disable
            // the button when the submit has not been cancelled by a prior
            // handler (jQuery Validate calls event.preventDefault() when the
            // form is invalid, so we skip the disable in that case and the
            // operator can fix + retry). jQuery Validate is bound at .validate()
            // time (line ~2390 above) which runs before this ready() callback,
            // so its handler is registered — and fires — first.
            $(document).ready(function () {
                $('#checkout_form').on('submit', function (event) {
                    if (event.isDefaultPrevented()) {
                        return;
                    }
                    $('#submit_button').prop('disabled', true);
                });
            });

            // Select encrypted custom fields to hide them in the asset list
            $(document).ready(function() {
                // Selector for elements with css-padlock class
                var selector = 'td.css-padlock';

                // Function to add original value to elements
                function addValue($element) {
                    var originalHtml = $element.html().trim();
                    var originalText = $element.text().trim();
                    var hasHtmlContent = originalHtml !== '' && originalHtml !== originalText;

                    // Show asterisks only for non-empty values
                    if (originalText !== '') {
                        var asterisks = '*'.repeat(11);
                        // Avoid reprocessing already-asterisked elements
                        if (originalText !== asterisks) {
                            if (hasHtmlContent) {
                                $element.data('encrypted-html', originalHtml);
                            }
                            $element.attr('value', originalText);
                        }

                        // Hide the original value and show a fixed-length asterisk placeholder
                        $element.text(asterisks);

                        // Add click event to show original value
                        $element.click(function() {
                            var $this = $(this);
                            if ($this.text().trim() === asterisks) {
                                var savedHtml = $this.data('encrypted-html');
                                if (savedHtml) {
                                    $this.html(savedHtml);
                                } else {
                                    $this.text($this.attr('value'));
                                }
                            } else {
                                $this.text(asterisks);
                            }
                        });
                    }
                }
                // Add value to existing elements
                $(selector).each(function() {
                    addValue($(this));
                });

                // Function to handle mutations in the DOM because content is generated dynamically
                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        // Check if new nodes have been inserted
                        if (mutation.type === 'childList') {
                            mutation.addedNodes.forEach(function(node) {
                                if ($(node).is(selector)) {
                                    addValue($(node));
                                } else {
                                    $(node).find(selector).each(function() {
                                        addValue($(this));
                                    });
                                }
                            });
                        }
                    });
                });

                // Configure the observer to observe changes in the DOM
                var config = { childList: true, subtree: true };
                observer.observe(document.body, config);
            });


        </script>

        @if ((session()->get('topsearch')=='true') || (request()->is('/')))
            <script nonce="{{ csrf_token() }}">
                $("#tagSearch").focus();
            </script>
        @endif

        @include('partials.theme-mode-toggle')

        </body>
</html>
