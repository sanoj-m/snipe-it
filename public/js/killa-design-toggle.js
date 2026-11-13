/* ==========================================================================
   [killa-v2 design-toggle] KCP-054 — New/Classic design switch

   "New" (default)  = v2 "Signal" look (tinted tiles, brand links, 24px titles)
   "Classic"        = v2.1 look (white tiles + color edges, stock link color)

   Mechanics: Classic is expressed entirely by html[data-design='classic']
   gating in killa-v2.css, so switching is instant — no reload. The choice
   persists in localStorage ('killa.design'); the pre-paint head script in
   layouts/default.blade.php stamps the attribute before first paint so the
   Classic look never flashes New.
   ========================================================================== */
(function () {
    'use strict';

    var KEY = 'killa.design';

    function current() {
        try {
            return localStorage.getItem(KEY) === 'classic' ? 'classic' : 'signal';
        } catch (e) {
            return 'signal';
        }
    }

    function apply(mode) {
        var root = document.documentElement;
        if (mode === 'classic') {
            root.setAttribute('data-design', 'classic');
        } else {
            root.removeAttribute('data-design');
        }
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

        var wrap = document.createElement('div');
        wrap.className = 'kdesign-toggle';
        wrap.setAttribute('role', 'group');
        wrap.setAttribute('aria-label', 'Design version');
        wrap.title = 'Switch between the new (Signal) and classic design';

        [['signal', 'New'], ['classic', 'Classic']].forEach(function (pair) {
            var b = document.createElement('button');
            b.type = 'button';
            b.dataset.mode = pair[0];
            b.textContent = pair[1];
            b.addEventListener('click', function () {
                try {
                    localStorage.setItem(KEY, pair[0]);
                } catch (e) {}
                apply(pair[0]);
                syncButtons(wrap, pair[0]);
            });
            wrap.appendChild(b);
        });

        host.appendChild(wrap);
        syncButtons(wrap, current());
    }

    apply(current());

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mount);
    } else {
        mount();
    }
})();
