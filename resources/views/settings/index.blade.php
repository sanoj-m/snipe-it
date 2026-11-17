@extends('layouts/default')

{{-- Page title --}}
@section('title')
    {{ trans('general.admin') }}
@parent
@stop

@section('header_right')



  <!-- search filter box -->
  <div class="pull-right">


    <form onsubmit="return false;" role="search" aria-label="Admin Options" id="setting-search">
      <div class="btn-group">
        <input id="searchinput" name="search" type="search" class="search form-control" placeholder="{{ trans('admin/settings/general.filter_by_keyword') }}" aria-label="keyword search">
        <span id="searchclear" class="fas fa-times" aria-hidden="true"></span>
        <button type="submit" disabled style="display: none" aria-hidden="true"></button>
      </div>
    </form>




  </div>
  <!--/ search filter box -->
@stop


{{-- Page content --}}
@section('content')



  <style>
    #searchinput {
      width: 200px;
    }
    #searchclear {
      position: absolute;
      right: 5px;
      top: 0;
      bottom: 0;
      height: 14px;
      margin: auto;
      font-size: 14px;
      cursor: pointer;
      color: #ccc;
    }
  </style>

  {{-- [killa-v2 admin] KCP-066 BEGIN — grouped, color-coded settings grid.
       All routes, icons, translation keys, permission gates and help texts are
       unchanged from upstream; only structure + classes changed. The filter is
       a small custom script (see moar_scripts) because upstream's List.js
       targets #setting-list, an id that does not exist, so the upstream
       search silently does nothing. Sections hide when all their tiles are
       filtered out. Styles: killa-v2.css "[killa-v2 admin] KCP-066" block. --}}
  <div class="kadmin" id="setting-list">

    {{-- Section: general & appearance --}}
    <section class="kadmin-section" data-kadmin-section>
      <header class="kadmin-section-head">
        <span class="kadmin-section-icon" data-accent="sky"><i class="fas fa-sliders-h" aria-hidden="true"></i></span>
        <h2 class="kadmin-section-title">{{ trans('killa.admin_sections.general') }}</h2>
      </header>
      <div class="kadmin-grid">

        <div class="kadmin-tile" data-accent="navy">
          <a href="{{ route('settings.general.index') }}" class="settings_button kadmin-link">
            <span class="kadmin-icon"><x-icon type="general-settings"/></span>
            <span class="kadmin-text">
              <span class="name">{{ trans('admin/settings/general.general_settings') }}</span>
              <span class="kadmin-help index-block">{{ trans('admin/settings/general.general_settings_help') }}</span>
            </span>
            <span class="keywords" aria-hidden="true" style="display:none">{{ trans('admin/settings/general.keywords.general_settings') }}</span>
          </a>
        </div>

        <div class="kadmin-tile" data-accent="sky">
          <a href="{{ route('settings.branding.index') }}" class="settings_button kadmin-link">
            <span class="kadmin-icon"><x-icon type="branding"/></span>
            <span class="kadmin-text">
              <span class="name">{{ trans('admin/settings/general.brand') }}</span>
              <span class="kadmin-help index-block">{{ trans('admin/settings/general.brand_help') }}</span>
            </span>
            <span class="keywords" aria-hidden="true" style="display:none">{{ trans('admin/settings/general.keywords.brand') }}</span>
          </a>
        </div>

        {{-- [killa currency] settings tile (KCP-041, now folded into the KCP-066 grid) --}}
        <div class="kadmin-tile" data-accent="amber">
          <a href="{{ route('settings.currency.index') }}" class="settings_button kadmin-link">
            <span class="kadmin-icon"><x-icon type="cost"/></span>
            <span class="kadmin-text">
              <span class="name">{{ trans('killa.currency.title') }}</span>
              <span class="kadmin-help index-block">{{ trans('killa.currency.tile_help') }}</span>
            </span>
          </a>
        </div>
        {{-- [killa currency] END --}}

        <div class="kadmin-tile" data-accent="teal">
          <a href="{{ route('settings.localization.index') }}" class="settings_button kadmin-link">
            <span class="kadmin-icon"><x-icon type="globe-us"/></span>
            <span class="kadmin-text">
              <span class="name">{{ trans('admin/settings/general.localization') }}</span>
              <span class="kadmin-help index-block">{{ trans('admin/settings/general.localization_help') }}</span>
            </span>
            <span class="keywords" aria-hidden="true" style="display:none">{{ trans('admin/settings/general.keywords.localization') }}</span>
          </a>
        </div>

        <div class="kadmin-tile" data-accent="emerald">
          <a href="{{ route('settings.labels.index') }}" class="settings_button kadmin-link">
            <span class="kadmin-icon"><x-icon type="labels"/></span>
            <span class="kadmin-text">
              <span class="name">{{ trans('admin/settings/general.labels') }}</span>
              <span class="kadmin-help index-block">{!! trans('admin/settings/general.labels_help') !!}</span>
            </span>
            <span class="keywords" aria-hidden="true" style="display:none">{{ trans('admin/settings/general.keywords.labels') }}</span>
          </a>
        </div>

        <div class="kadmin-tile" data-accent="orange">
          <a href="{{ route('settings.asset_tags.index') }}" class="settings_button kadmin-link">
            <span class="kadmin-icon"><x-icon type="asset-tags"/></span>
            <span class="kadmin-text">
              <span class="name">{{ trans('general.asset_tags') }}</span>
              <span class="kadmin-help index-block">{{ trans('admin/settings/general.asset_tags_help') }}</span>
            </span>
          </a>
        </div>

      </div>
    </section>

    {{-- Section: security & access --}}
    <section class="kadmin-section" data-kadmin-section>
      <header class="kadmin-section-head">
        <span class="kadmin-section-icon" data-accent="navy"><i class="fas fa-shield-alt" aria-hidden="true"></i></span>
        <h2 class="kadmin-section-title">{{ trans('killa.admin_sections.security') }}</h2>
      </header>
      <div class="kadmin-grid">

        <div class="kadmin-tile" data-accent="navy">
          <a href="{{ route('settings.security.index') }}" class="settings_button kadmin-link">
            <span class="kadmin-icon"><x-icon type="locked"/></span>
            <span class="kadmin-text">
              <span class="name">{{ trans('admin/settings/general.security') }}</span>
              <span class="kadmin-help index-block">{{ trans('admin/settings/general.security_help') }}</span>
            </span>
            <span class="keywords" aria-hidden="true" style="display:none">{{ trans('admin/settings/general.keywords.security') }}</span>
          </a>
        </div>

        <div class="kadmin-tile" data-accent="teal">
          <a href="{{ route('groups.index') }}" class="settings_button kadmin-link">
            <span class="kadmin-icon"><x-icon type="groups"/></span>
            <span class="kadmin-text">
              <span class="name">{{ trans('general.groups') }}</span>
              <span class="kadmin-help index-block">{{ trans('admin/settings/general.groups_help') }}</span>
            </span>
            <span class="keywords" aria-hidden="true" style="display:none">{{ trans('admin/settings/general.keywords.groups') }}</span>
          </a>
        </div>

        <div class="kadmin-tile" data-accent="sky">
          <a href="{{ route('settings.ldap.wizard') }}" class="settings_button kadmin-link">
            <span class="kadmin-icon"><x-icon type="ldap"/></span>
            <span class="kadmin-text">
              <span class="name">{{ trans('admin/settings/general.ldap') }}</span>
              <span class="kadmin-help index-block">{{ trans('admin/settings/general.ldap_help') }}</span>
            </span>
          </a>
        </div>

        <div class="kadmin-tile" data-accent="orange">
          <a href="{{ route('settings.saml.index') }}" class="settings_button kadmin-link">
            <span class="kadmin-icon"><x-icon type="saml"/></span>
            <span class="kadmin-text">
              <span class="name">{{ trans('admin/settings/general.saml') }}</span>
              <span class="kadmin-help index-block">{{ trans('admin/settings/general.saml_help') }}</span>
            </span>
          </a>
        </div>

        <div class="kadmin-tile" data-accent="emerald">
          <a href="{{ route('settings.google.index') }}" class="settings_button kadmin-link">
            <span class="kadmin-icon"><x-icon type="google"/></span>
            <span class="kadmin-text">
              <span class="name">Google</span>
              <span class="kadmin-help index-block">{{ trans('admin/settings/general.google_login') }}</span>
            </span>
          </a>
        </div>

        <div class="kadmin-tile" data-accent="amber">
          <a href="{{ route('settings.logins.index') }}" class="settings_button kadmin-link">
            <span class="kadmin-icon"><x-icon type="logins"/></span>
            <span class="kadmin-text">
              <span class="name">{{ trans('admin/settings/general.login') }}</span>
              <span class="kadmin-help index-block">{{ trans('admin/settings/general.login_help') }}</span>
            </span>
          </a>
        </div>

        <div class="kadmin-tile" data-accent="navy">
          <a href="{{ route('settings.oauth.index') }}" class="settings_button kadmin-link">
            <span class="kadmin-icon"><x-icon type="oauth"/></span>
            <span class="kadmin-text">
              <span class="name">{{ trans('admin/settings/general.oauth') }}</span>
              <span class="kadmin-help index-block">{{ trans('admin/settings/general.oauth_help') }}</span>
            </span>
            <span class="keywords" aria-hidden="true" style="display:none">{{ trans('admin/settings/general.keywords.oauth') }}</span>
          </a>
        </div>

      </div>
    </section>

    {{-- Section: data & integrations --}}
    <section class="kadmin-section" data-kadmin-section>
      <header class="kadmin-section-head">
        <span class="kadmin-section-icon" data-accent="amber"><i class="fas fa-plug" aria-hidden="true"></i></span>
        <h2 class="kadmin-section-title">{{ trans('killa.admin_sections.integrations') }}</h2>
      </header>
      <div class="kadmin-grid">

        <div class="kadmin-tile" data-accent="amber">
          <a href="{{ route('settings.alerts.index') }}" class="settings_button kadmin-link">
            <span class="kadmin-icon"><x-icon type="bell"/></span>
            <span class="kadmin-text">
              <span class="name">{{ trans('admin/settings/general.notifications') }}</span>
              <span class="kadmin-help index-block">{{ trans('admin/settings/general.notifications_help') }}</span>
            </span>
            <span class="keywords" aria-hidden="true" style="display:none">{{ trans('admin/settings/general.keywords.notifications') }}</span>
          </a>
        </div>

        <div class="kadmin-tile" data-accent="sky">
          <a href="{{ route('settings.integrations.index') }}" class="settings_button kadmin-link">
            <span class="kadmin-icon"><x-icon type="hashtag"/></span>
            <span class="kadmin-text">
              <span class="name">{{ trans('admin/settings/general.integrations') }}</span>
              <span class="kadmin-help index-block">{{ trans('admin/settings/general.webhook_help') }}</span>
            </span>
          </a>
        </div>

        <div class="kadmin-tile" data-accent="teal">
          <a href="{{ route('settings.adapters.index') }}" class="settings_button kadmin-link">
            <span class="kadmin-icon"><i class="fa-solid fa-network-wired" aria-hidden="true"></i></span>
            <span class="kadmin-text">
              <span class="name">{{ trans('admin/settings/sync_adapters.title') }} <span class="label label-warning">beta</span></span>
              <span class="kadmin-help index-block">{{ trans('admin/settings/sync_adapters.help') }}</span>
            </span>
            <span class="keywords" aria-hidden="true" style="display:none">{{ $adapterKeywords }}</span>
          </a>
        </div>

        <div class="kadmin-tile" data-accent="emerald">
          <a href="{{ route('settings.backups.index') }}" class="settings_button kadmin-link">
            <span class="kadmin-icon"><x-icon type="backups"/></span>
            <span class="kadmin-text">
              <span class="name">{{ trans('admin/settings/general.backups') }}</span>
              <span class="kadmin-help index-block">{!! trans('admin/settings/general.backups_help') !!}</span>
            </span>
          </a>
        </div>

        @if (config('app.debug')=== true)
        <div class="kadmin-tile" data-accent="navy">
          <a href="{{ route('settings.phpinfo.index') }}" class="settings_button kadmin-link">
            <span class="kadmin-icon"><i class="fab fa-php" aria-hidden="true"></i></span>
            <span class="kadmin-text">
              <span class="name">{{ trans('admin/settings/general.php_overview') }}</span>
              <span class="kadmin-help index-block">{{ trans('admin/settings/general.php_overview_help') }}</span>
            </span>
            <span class="keywords" aria-hidden="true" style="display:none">{{ trans('admin/settings/general.keywords.php_overview') }}</span>
          </a>
        </div>
        @endif

      </div>
    </section>

    {{-- Section: danger zone --}}
    <section class="kadmin-section" data-kadmin-section>
      <header class="kadmin-section-head">
        <span class="kadmin-section-icon" data-accent="rose"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i></span>
        <h2 class="kadmin-section-title">{{ trans('killa.admin_sections.danger') }}</h2>
      </header>
      <div class="kadmin-grid">

        <div class="kadmin-tile kadmin-tile-danger" data-accent="rose">
          <a href="{{ route('settings.purge.index') }}" class="settings_button kadmin-link link-danger">
            <span class="kadmin-icon"><i class="fas fa-trash" aria-hidden="true"></i></span>
            <span class="kadmin-text">
              <span class="name">{{ trans('admin/settings/general.purge') }}</span>
              <span class="kadmin-help index-block">{{ trans('admin/settings/general.purge_help') }}</span>
            </span>
            <span class="keywords" aria-hidden="true" style="display:none">{{ trans('admin/settings/general.keywords.purge') }}</span>
          </a>
        </div>

      </div>
    </section>

    <p class="kadmin-empty text-muted" data-kadmin-empty hidden>{{ trans('killa.admin_sections.no_results') }}</p>

  </div>
  {{-- [killa-v2 admin] KCP-066 END --}}



<div class="row">
  <div class="col-md-12">
    <div class="box box-default">
      <div class="box-header">
        <h2 class="box-title">{{ trans('admin/settings/general.system') }}</h2>
      </div>
      <div class="box-body">
        <div class="row-new-striped">

          <!-- row -->
          <div class="row">
            <div class="col-md-2">
              <strong>{{ trans('admin/settings/general.snipe_version') }}:</strong>
            </div>
            <div class="col-md-4">
              {{ config('version.app_version') }}  build {{ config('version.build_version') }} ({{ config('version.hash_version') }})
            </div>

            <div class="col-md-2">
              <strong>{{ trans('admin/settings/general.license') }}:</strong>
            </div>
            <div class="col-md-4">
                AGPL3
                <a href="https://www.gnu.org/licenses/agpl-3.0.en.html" target="_blank" data-tooltip="true" title="{{ trans('general.documentation') }}"><x-icon type="external-link" /></a>
           </div>
          </div>
          <!-- / row -->


          <!-- row -->
          <div class="row">
            <div class="col-md-2">
              <strong>{{ trans('admin/settings/general.php') }}:</strong>
            </div>
            <div class="col-md-4">
              {{ phpversion() }}
            </div>

            <div class="col-md-2">
              <strong>{{ trans('admin/settings/general.laravel') }}:</strong>
            </div>
            <div class="col-md-4">
              {{ $snipeSettings->lar_ver() }}
            </div>
          </div>

          <!-- row -->
          <div class="row">
              <div class="col-md-2">
                <strong>{{ trans('admin/settings/general.timezone') }}:</strong>
              </div>
              <div class="col-md-4">
                {{ config('app.timezone') }}
              </div>

              <div class="col-md-2">
                <strong>{{ trans('admin/settings/general.database_driver') }}:</strong>
              </div>
              <div class="col-md-4">
                {{ config('database.default') }}
              </div>
          </div>

          <!-- row -->
          <div class="row">
            <div class="col-md-2">
              <strong>{{ trans('admin/settings/general.mail_from') }}:</strong>
            </div>
            <div class="col-md-4">
              {{ config('mail.from.name') }}
              <code>&lt;{{ config('mail.from.address') }}&gt;</code>
            </div>

            <div class="col-md-2">
              <strong>{{ trans('admin/settings/general.mail_reply_to') }}:</strong>
            </div>
            <div class="col-md-4">
              {{ config('mail.reply_to.name') }}
              <code>&lt;{{ config('mail.reply_to.address') }}&gt;</code>
            </div>
          </div>

            <!-- row -->
            <div class="row">
                <div class="col-md-2">
                    <strong>{{ trans('admin/settings/general.api_url') }}:</strong>
                </div>
                <div class="col-md-4">
                    <code>{{ url('/api/v1') }}{!! trans('account/general.api_base_url_endpoint') !!}</code>
                    <a href="https://snipe-it.readme.io/reference/api-overview" target="_blank" data-tooltip="true" title="{{ trans('general.documentation') }}"><x-icon type="external-link" /></a>

                </div>

                <div class="col-md-2">
                    <strong>{{ trans('admin/settings/general.scim_url') }}:</strong>
                </div>
                <div class="col-md-4">
                    <code>{{ config('app.url') }}/scim/v2/</code>
                    <a href="https://snipe-it.readme.io/docs/scim" target="_blank" data-tooltip="true" title="{{ trans('general.documentation') }}"><x-icon type="external-link" /></a>

                </div>
            </div>
            <!-- / row -->

          <!-- row -->
          <div class="row">
            <div class="col-md-2">
              <strong>{{ trans('admin/settings/general.bs_table_storage') }}:</strong>
            </div>
            <div class="col-md-4">
              {{ config('session.bs_table_storage') }}
            </div>
            <div class="col-md-2">
            </div>
            <div class="col-md-4">
            </div>
          </div>

            <!-- row -->
            <div class="row">
                <div class="col-md-2">
                    <strong>{{ trans('admin/settings/general.user_impersonation') }}:</strong>
                </div>
                <div class="col-md-10">
                    @if ($impersonators->isEmpty() && empty($missingImpersonationUsernames))
                        <em class="text-muted">{{ trans('admin/settings/general.user_impersonation_disabled') }}</em>
                    @else
                        <ul>
                            @foreach ($impersonators as $impersonator)
                                <li>
                                    <a href="{{ route('users.show', $impersonator->id) }}">{{ $impersonator->display_name }}</a>
                                    <code>{{ $impersonator->username }}</code>
                                    @if (! $impersonator->isSuperUser())
                                        <span class="label label-warning" data-tooltip="true" title="{{ trans('admin/settings/general.user_impersonation_not_superuser_help') }}">
                          {{ trans('admin/settings/general.user_impersonation_not_superuser') }}
                        </span>
                                    @endif
                                    @if ($impersonator->deleted_at !== null)
                                        <span class="label label-danger">{{ trans('general.deleted') }}</span>
                                    @elseif ($impersonator->activated != 1)
                                        <span class="label label-default">{{ trans('admin/settings/general.user_impersonation_deactivated') }}</span>
                                    @endif
                                </li>
                            @endforeach
                            @foreach ($missingImpersonationUsernames as $missingUsername)
                                <li>
                                    <code>{{ $missingUsername }}</code>
                                    <span class="label label-danger">{{ trans('admin/settings/general.user_impersonation_missing') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
        </div>
          </div>
          <!--/ row -->

      </div> <!-- /box-body-->
    </div> <!--/box-default-->






  @section('moar_scripts')
{{-- [killa-v2 admin] KCP-066 BEGIN — tile filter (replaces upstream List.js
     init, which silently failed: no element with id "setting-list" existed).
     Filters tiles on .name + .keywords text, hides empty sections, shows a
     no-results line. --}}
<script nonce="{{ csrf_token() }}">

  (function () {
    var input = document.getElementById('searchinput');
    var clear = document.getElementById('searchclear');
    var root = document.getElementById('setting-list');
    if (!input || !root) { return; }

    var tiles = root.querySelectorAll('.kadmin-tile');
    var sections = root.querySelectorAll('[data-kadmin-section]');
    var empty = root.querySelector('[data-kadmin-empty]');

    function applyFilter() {
      var q = input.value.trim().toLowerCase();
      var anyVisible = false;

      tiles.forEach(function (tile) {
        var haystack = tile.textContent.toLowerCase();
        var show = q === '' || haystack.indexOf(q) !== -1;
        tile.classList.toggle('kadmin-tile-hidden', !show);
        if (show) { anyVisible = true; }
      });

      sections.forEach(function (section) {
        var visible = section.querySelectorAll('.kadmin-tile:not(.kadmin-tile-hidden)').length > 0;
        section.style.display = visible ? '' : 'none';
      });

      if (empty) { empty.hidden = anyVisible; }
    }

    input.addEventListener('input', applyFilter);
    if (clear) {
      clear.addEventListener('click', function () {
        input.value = '';
        applyFilter();
        input.focus();
      });
    }
  })();

</script>
{{-- [killa-v2 admin] KCP-066 END --}}
  @endsection

@stop
