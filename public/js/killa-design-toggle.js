/* ==========================================================================
   [killa-v2 design-toggle] KCP-054 — New/Classic design switch

   "New" (default)  = v2 "Signal" look (fixed topbar, tinted tiles, brand
                      links, animated rail)
   "Classic"        = genuine stock Snipe-IT chrome: the server skips every
                      killa-v2 overlay stylesheet and renders the preserved
                      upstream sidebar markup (killa-classic-sidebar partial).

   Mechanics: the choice persists in the `killa_design` COOKIE, which
   layouts/default.blade.php reads at render time (localStorage
   'killa.design' is kept as a legacy mirror). Switching reloads the page so
   the server can render the other design — a live CSS swap is not possible
   because Classic loads a different stylesheet set and sidebar markup.
   ========================================================================== */
(function () {
    'use strict';

    var COOKIE = 'killa_design';
    var LEGACY_KEY = 'killa.design';

    function readCookie() {
        var m = document.cookie.match(/(?:^|;\s*)killa_design=([^;]*)/);
        return m ? decodeURIComponent(m[1]) : null;
    }

    function current() {
        return readCookie() === 'classic' ? 'classic' : 'signal';
    }

    /* The pill's styles live in killa-v2.css, which Classic never loads —
       inject a neutral fallback that works on any tenant header color. */
    function ensureFallbackStyles() {
        if (document.querySelector('link[href*="killa-v2.css"]')) {
            return;
        }
        if (document.getElementById('kdesign-toggle-fallback')) {
            return;
        }
        var st = document.createElement('style');
        st.id = 'kdesign-toggle-fallback';
        st.textContent =
            '.kdesign-toggle{display:inline-flex;align-items:center;gap:2px;margin-left:12px;padding:2px;background:#ffffff;border:1px solid rgba(0,0,0,.18);border-radius:999px;box-shadow:0 1px 2px rgba(0,0,0,.12)}' +
            '.kdesign-toggle button{border:0;background:transparent;cursor:pointer;font-family:inherit;font-size:11px;font-weight:600;line-height:1.4;color:#444;padding:3px 10px;border-radius:999px}' +
            '.kdesign-toggle button:hover{color:#000}' +
            '.kdesign-toggle button[aria-pressed="true"]{background:#1f2933;color:#fff}';
        document.head.appendChild(st);
    }

    function syncButtons(wrap, mode) {
        Array.prototype.forEach.call(wrap.querySelectorAll('button'), function (b) {
            b.setAttribute('aria-pressed', String(b.dataset.mode === mode));
        });
    }

    function mount() {
        var host = document.querySelector('.main-header .left-navblock');
        if (!host || host.querySelector('.kdesign-toggle')) {
            return;
        }

        ensureFallbackStyles();

        var wrap = document.createElement('div');
        wrap.className = 'kdesign-toggle';
        wrap.setAttribute('role', 'group');
        wrap.setAttribute('aria-label', 'Design version');
        wrap.title = 'Switch between the new (Signal) and classic (stock Snipe-IT) design — reloads the page';

        [['signal', 'New'], ['classic', 'Classic']].forEach(function (pair) {
            var b = document.createElement('button');
            b.type = 'button';
            b.dataset.mode = pair[0];
            b.textContent = pair[1];
            b.addEventListener('click', function () {
                try {
                    localStorage.setItem(LEGACY_KEY, pair[0]);
                } catch (e) {}
                document.cookie = COOKIE + '=' + pair[0] + '; path=/; max-age=31536000; samesite=lax';
                window.location.reload();
            });
            wrap.appendChild(b);
        });

        host.appendChild(wrap);
        syncButtons(wrap, current());
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mount);
    } else {
        mount();
    }
})();
