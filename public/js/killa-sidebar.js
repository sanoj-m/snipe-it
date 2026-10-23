/* [killa-v2 sidebar] KCP-049 — vanilla controller for the ks-* sidebar rail.
   Loaded with `defer` after all.js in layouts/default.blade.php.
   Collapse state contract:
     - AdminLTE PushMenu remains the source of truth via `body.sidebar-collapse`.
     - This script mirrors it onto `body.ks-collapsed` and persists it in
       localStorage('ks.sidebar'). A MutationObserver syncs the mirror whenever
       AdminLTE (or anything else) flips `sidebar-collapse`.
     - The pre-paint head script stamps `html.ks-collapsed-pre`; we resolve it
       here before first paint.
   Guards: everything no-ops on pages without `#ks-sidebar` (basic layout). */
(function () {
    'use strict';

    var STORAGE_KEY = 'ks.sidebar';
    var MOBILE_BP = 768;
    var FLYOUT_DELAY = 500; // hover-intent hold for collapsed flyout menus
    var reducedMotion = window.matchMedia
        && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var body = document.body;
    var sidebar = document.getElementById('ks-sidebar');
    var root = document.documentElement;

    /* Resolve the pre-paint class even when the sidebar is absent. */
    var preCollapsed = root.classList.contains('ks-collapsed-pre');
    root.classList.remove('ks-collapsed-pre');

    if (!sidebar) {
        return;
    }

    var scrim = document.querySelector('.ks-scrim');
    var railToggle = document.getElementById('ks-rail-toggle');

    function isMobile() {
        return window.innerWidth < MOBILE_BP;
    }

    function storedState() {
        try {
            return localStorage.getItem(STORAGE_KEY);
        } catch (e) {
            return null;
        }
    }

    function persistState(collapsed) {
        try {
            localStorage.setItem(STORAGE_KEY, collapsed ? 'collapsed' : 'expanded');
        } catch (e) { /* private mode etc. — state simply does not persist */ }
    }

    /* ------------------------------------------------------------------
       Collapse state
       ------------------------------------------------------------------ */
    function isCollapsed() {
        return body.classList.contains('ks-collapsed');
    }

    function applyCollapsed(collapsed, persist) {
        body.classList.toggle('sidebar-collapse', collapsed);
        body.classList.toggle('ks-collapsed', collapsed);
        if (persist) {
            persistState(collapsed);
        }
        if (!collapsed) {
            sidebar.querySelectorAll('.ks-item--parent.ks-flyout-hold').forEach(function (li) {
                li.classList.remove('ks-flyout-hold');
            });
        }
        schedulePill();
    }

    function toggleCollapse() {
        applyCollapsed(!isCollapsed(), true);
    }

    /* Initial state: pre-paint marker → localStorage → session body class. */
    var initialCollapsed = preCollapsed
        || storedState() === 'collapsed'
        || (storedState() === null && body.classList.contains('sidebar-collapse'));
    applyCollapsed(initialCollapsed, false);

    /* Mirror any external sidebar-collapse change (AdminLTE PushMenu). */
    var syncing = false;
    new MutationObserver(function () {
        if (syncing) {
            return;
        }
        var collapsed = body.classList.contains('sidebar-collapse');
        if (collapsed !== isCollapsed()) {
            syncing = true;
            applyCollapsed(collapsed, true);
            syncing = false;
        }
    }).observe(body, { attributes: true, attributeFilter: ['class'] });

    /* Hamburger: on mobile we run the drawer ourselves; on desktop let
       AdminLTE PushMenu toggle sidebar-collapse and the observer syncs. */
    document.querySelectorAll('[data-toggle="push-menu"]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            if (isMobile()) {
                e.preventDefault();
                e.stopPropagation();
                toggleDrawer();
            }
        }, true);
    });

    if (railToggle) {
        railToggle.addEventListener('click', function () {
            if (isMobile()) {
                toggleDrawer();
            } else {
                toggleCollapse();
            }
        });
    }

    /* Ctrl/Cmd+B toggles the rail. */
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && !e.shiftKey && !e.altKey && (e.key === 'b' || e.key === 'B')) {
            e.preventDefault();
            toggleCollapse();
        }
    });

    /* ------------------------------------------------------------------
       Accordion submenus (one open per group). Clicking a parent while the
       rail is collapsed expands the rail first (reference behavior).
       ------------------------------------------------------------------ */
    sidebar.querySelectorAll('.ks-item--parent > .ks-link').forEach(function (link) {
        var item = link.parentElement;
        link.addEventListener('click', function (e) {
            e.preventDefault();
            if (isCollapsed() && !isMobile()) {
                applyCollapsed(false, true);
                openItem(item, true);
                return;
            }
            openItem(item, !item.classList.contains('open'));
        });
    });

    function openItem(item, open) {
        if (open) {
            var group = item.closest('.ks-menu');
            if (group) {
                group.querySelectorAll('.ks-item--parent.open').forEach(function (other) {
                    if (other !== item) {
                        setOpen(other, false);
                    }
                });
            }
        }
        setOpen(item, open);
        schedulePill();
    }

    function setOpen(item, open) {
        item.classList.toggle('open', open);
        var link = item.querySelector('.ks-link');
        if (link) {
            link.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
    }

    /* Auto-open the parent that contains the active child. */
    sidebar.querySelectorAll('.ks-item--parent').forEach(function (item) {
        if (item.classList.contains('active') || item.querySelector('.ks-sub .active')) {
            setOpen(item, true);
        }
    });

    /* ------------------------------------------------------------------
       Sliding active pill: measure the active top-level item of each group
       menu and position that menu's pill on it.
       ------------------------------------------------------------------ */
    var pillScheduled = false;

    function schedulePill() {
        if (pillScheduled) {
            return;
        }
        pillScheduled = true;
        window.requestAnimationFrame(function () {
            pillScheduled = false;
            positionPills();
        });
    }

    function positionPills() {
        sidebar.querySelectorAll('.ks-menu').forEach(function (menu) {
            var active = null;
            var items = menu.querySelectorAll(':scope > .ks-item');
            for (var i = 0; i < items.length; i++) {
                if (items[i].classList.contains('active')) {
                    active = items[i];
                    break;
                }
            }
            if (!active) {
                menu.classList.remove('ks-pill-on');
                return;
            }
            menu.style.setProperty('--pill-y', active.offsetTop + 'px');
            menu.style.setProperty('--pill-h', active.offsetHeight + 'px');
            menu.classList.add('ks-pill-on');
        });
    }

    window.addEventListener('resize', schedulePill);
    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(schedulePill);
    }
    schedulePill();

    /* ------------------------------------------------------------------
       Hover-intent flyout in collapsed mode: instant open on enter,
       delayed close on leave so the pointer can cross into the flyout.
       ------------------------------------------------------------------ */
    sidebar.querySelectorAll('.ks-item--parent').forEach(function (item) {
        var timer = null;
        item.addEventListener('mouseenter', function () {
            if (!isCollapsed() || isMobile()) {
                return;
            }
            clearTimeout(timer);
            sidebar.querySelectorAll('.ks-item--parent.ks-flyout-hold').forEach(function (other) {
                if (other !== item) {
                    other.classList.remove('ks-flyout-hold');
                }
            });
            item.classList.add('ks-flyout-hold');
        });
        item.addEventListener('mouseleave', function () {
            if (!isCollapsed() || isMobile()) {
                return;
            }
            clearTimeout(timer);
            timer = setTimeout(function () {
                item.classList.remove('ks-flyout-hold');
            }, reducedMotion ? 0 : FLYOUT_DELAY);
        });
    });

    /* ------------------------------------------------------------------
       Mobile drawer: scrim click, Esc, Tab focus trap, focus restore,
       scroll lock preserving scrollY.
       ------------------------------------------------------------------ */
    var drawerTrigger = null;
    var lockedScrollY = 0;

    function drawerOpen() {
        return body.classList.contains('ks-drawer-open');
    }

    function toggleDrawer() {
        if (drawerOpen()) {
            closeDrawer();
        } else {
            openDrawer();
        }
    }

    function openDrawer() {
        drawerTrigger = document.activeElement;
        lockedScrollY = window.scrollY;
        body.classList.add('ks-drawer-open', 'ks-lock');
        var first = sidebar.querySelector('.ks-link');
        if (first) {
            first.focus();
        }
    }

    function closeDrawer() {
        body.classList.remove('ks-drawer-open', 'ks-lock');
        window.scrollTo(0, lockedScrollY);
        if (drawerTrigger && drawerTrigger.focus) {
            drawerTrigger.focus();
        }
        drawerTrigger = null;
    }

    if (scrim) {
        scrim.addEventListener('click', closeDrawer);
    }

    document.addEventListener('keydown', function (e) {
        if (!drawerOpen()) {
            return;
        }
        if (e.key === 'Escape') {
            e.preventDefault();
            closeDrawer();
            return;
        }
        if (e.key === 'Tab') {
            trapTab(e);
        }
    });

    function trapTab(e) {
        var focusables = sidebar.querySelectorAll('a[href], button:not([disabled])');
        if (!focusables.length) {
            return;
        }
        var first = focusables[0];
        var last = focusables[focusables.length - 1];
        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    }

    /* Leaving mobile viewport with the drawer open: reset state. */
    window.addEventListener('resize', function () {
        if (!isMobile() && drawerOpen()) {
            closeDrawer();
        }
    });
})();
