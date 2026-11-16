{{-- [killa lan-switch banner] KCP-060 — Killa-owned partial (included from layouts/default.blade.php).
     Shown when a request arrives at the canonical host from a whitelisted office
     WAN IP (KILLA_LAN_WAN_IPS, matched against CF-Connecting-IP): offers a
     one-click switch to the local URL (KILLA_LAN_URL). Dismissal persists in
     localStorage. Never rendered on alt-host (LAN) requests or when unconfigured. --}}
@php
    $killaLanIps = array_filter(array_map('trim', explode(',', (string) config('app.lan_wan_ips'))));
    $killaLanUrl = rtrim((string) config('app.lan_url'), '/');
    $killaClientIp = request()->header('CF-Connecting-IP') ?: request()->ip();
    $killaAltHosts = array_filter(array_map('trim', explode(',', (string) config('app.alt_hosts'))));
@endphp
@if ($killaLanUrl !== '' && count($killaLanIps) > 0 && $killaClientIp && in_array($killaClientIp, $killaLanIps, true) && ! in_array(request()->getHost(), $killaAltHosts, true))
    <div class="alert alert-info" id="killa-lan-banner" style="display:none; margin:10px 15px 0;">
        <button type="button" class="close" data-killa-lan-dismiss aria-label="{{ trans('general.close') }}">&times;</button>
        <i class="fas fa-network-wired" aria-hidden="true"></i>
        {{ trans('killa.lan_banner_text') }}
        <a href="{{ $killaLanUrl.request()->getRequestUri() }}" class="btn btn-sm btn-primary" style="margin-left:8px;">
            {{ trans('killa.lan_banner_button') }}
        </a>
    </div>
    <script>
        (function () {
            var KEY = 'killa.lan-banner.dismissed';
            var el = document.getElementById('killa-lan-banner');
            try { if (localStorage.getItem(KEY)) { el.remove(); return; } } catch (e) {}
            el.style.display = '';
            el.querySelector('[data-killa-lan-dismiss]').addEventListener('click', function () {
                try { localStorage.setItem(KEY, '1'); } catch (e) {}
                el.remove();
            });
        })();
    </script>
@endif
{{-- [/killa lan-switch banner] --}}
