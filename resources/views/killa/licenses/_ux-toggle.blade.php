{{-- [killa-ux] Floating old/new design switch for the licenses section.
     Included from layouts/default.blade.php on licenses* routes only.
     Self-contained inline styles so it looks right on BOTH the classic
     AdminLTE design and the .kx redesign (must not depend on killa-ux.css). --}}
@php($kxUxOn = session('killa_licenses_ux', false))
<style>
    .kx-ux-toggle {
        position: fixed;
        right: 20px;
        bottom: 20px;
        z-index: 1050;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 14px;
        border-radius: 999px;
        background-color: #2b2620;
        color: #ffffff;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.02em;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
        cursor: pointer;
        text-decoration: none;
    }

    .kx-ux-toggle:hover,
    .kx-ux-toggle:focus-visible {
        background-color: #E63B2E;
        color: #ffffff;
        text-decoration: none;
        outline: 2px solid rgba(230, 59, 46, 0.4);
        outline-offset: 2px;
    }

    @media print {
        .kx-ux-toggle { display: none !important; }
    }
</style>
<a href="{{ route('licenses.ux-toggle') }}"
   class="kx-ux-toggle hidden-print"
   data-tooltip="true"
   title="{{ $kxUxOn ? trans('killa.licenses_ux.back_to_classic') : trans('killa.licenses_ux.try_new_design') }}">
    <i class="fas fa-exchange-alt" aria-hidden="true"></i>
    <span>{{ $kxUxOn ? trans('killa.licenses_ux.back_to_classic') : trans('killa.licenses_ux.try_new_design') }}</span>
</a>
