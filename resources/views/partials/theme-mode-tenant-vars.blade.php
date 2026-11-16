{{-- Tenant color overrides for the theme-mode CSS variables.

     The static portion of what used to live in default.blade.php's
     inline <style> block moved to resources/assets/less/overrides.less
     (compiled into all.css). Only the variables that admins can
     customize via Settings > Branding stay inline so their chosen
     values interpolate at request time.

     Cascade order: overrides.less loads first (via all.css <link>),
     this inline block loads second in <head>, so these values win
     for tenants who have picked their own.

     Included from both layouts/default.blade.php and
     layouts/basic.blade.php so login and error pages carry the
     tenant's brand colors too. --}}
<style>
    :root {
        --main-theme-color: {{ $snipeSettings->header_color ?? '#3c8dbc' }};
        --btn-theme-text-color: {{ $nav_link_color ?? 'light-dark(hsl(from var(--main-theme-color) h s calc(l + 10)),hsl(from var(--main-theme-color) h s calc(l - 10)))' }};
        --nav-hover-text-color: {{ $nav_link_color ?? 'hsl(from var(--main-theme-color) h s calc(l - 10))' }};
        --nav-primary-text-color: {{ $nav_link_color ?? '#ffffff' }};
        --default-label-link-text: light-dark({{ $link_light_color ?? '#296282' }}, {{ $link_dark_color ?? '#5fa4cc' }});
    }
    [data-theme="light"] {
        --link-color: {{ $link_light_color ?? '#296282' }};
    }
    [data-theme="dark"] {
        --link-color: {{ $link_dark_color ?? '#5fa4cc' }};
    }
</style>

{{-- [killa-v2 branding] KCP-057: route tenant (and per-user, via
     CheckColorSettings) branding colors into the v2 token layer. The v2
     overlay pins the navbar to --k-navbar-bg and links to --k-link, which
     left Settings > Branding inert in the v2 design. Only the tokens the
     branding page controls are mapped; --k-accent stays the Killa brand.
     Navbar text honors the admin/user nav_link_color, with a WCAG contrast
     guard: below ~3:1 against the chosen header color we auto-pick dark or
     white text so a legacy white-on-white combo can never render. Inert in
     Classic (the v2 layer is not loaded there, KCP-054). --}}
@php
    $killaRelLum = function ($hex) {
        $hex = ltrim(trim((string) $hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return null;
        }
        $lin = function ($c) {
            $c = $c / 255;
            return $c <= 0.03928 ? $c / 12.92 : pow(($c + 0.055) / 1.055, 2.4);
        };
        return 0.2126 * $lin(hexdec(substr($hex, 0, 2)))
             + 0.7152 * $lin(hexdec(substr($hex, 2, 2)))
             + 0.0722 * $lin(hexdec(substr($hex, 4, 2)));
    };
    $killaContrast = function ($l1, $l2) {
        return (max($l1, $l2) + 0.05) / (min($l1, $l2) + 0.05);
    };

    $killaHeaderBg = trim((string) ($snipeSettings->header_color ?? ''));
    // The Setting accessor (app/Models/Setting.php) sanitizes NULL to the
    // upstream default '#3c8dbc', so the value is never empty at render —
    // treat the default as "not customized": the v2 header keeps its light
    // token surface unless the admin picks a non-default color.
    $killaHeaderCustom = ($killaHeaderBg !== '') && (strtolower($killaHeaderBg) !== '#3c8dbc');
    $killaNavText = trim((string) ($nav_link_color ?? ''));
    if ($killaHeaderCustom) {
        $killaHeaderLum = $killaRelLum($killaHeaderBg);
        $killaAutoText = (($killaHeaderLum !== null) && ($killaHeaderLum > 0.35)) ? '#1f2933' : '#ffffff';
        $killaNavTextFinal = ($killaNavText !== '') ? $killaNavText : $killaAutoText;
        $killaNavLum = $killaRelLum($killaNavTextFinal);
        if (($killaHeaderLum !== null) && ($killaNavLum !== null)
            && ($killaContrast($killaHeaderLum, $killaNavLum) < 3)) {
            $killaNavTextFinal = $killaAutoText;
        }
    }
@endphp
<style>
    @if ($killaHeaderCustom ?? false)
    :root,
    html[data-theme='dark'] {
        --k-navbar-bg: {{ $killaHeaderBg }};
        --k-navbar-text: {{ $killaNavTextFinal }};
        --k-navbar-title: {{ $killaNavTextFinal }};
    }
    @endif
    html[data-theme='light'] {
        --k-link: {{ $link_light_color ?? '#296282' }};
        --k-link-hover: color-mix(in srgb, var(--k-link) 80%, #000);
    }
    html[data-theme='dark'] {
        --k-link: {{ $link_dark_color ?? '#5fa4cc' }};
        --k-link-hover: color-mix(in srgb, var(--k-link) 80%, #fff);
    }
</style>
{{-- [/killa-v2 branding] --}}
