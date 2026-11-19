/* [killa global-search] KCP-061: header global search typeahead.
   Plain committed script (no build step), loaded deferred from
   layouts/default.blade.php in the v2 design only. Talks to
   api.killa.search with the session X-CSRF-TOKEN header (same pattern as
   the bootstrap-table datatables), debounces at 200ms, minimum 2 chars.

   All rendering goes through textContent/DOM nodes — result titles and
   subtitles come straight from the database, never innerHTML. */
(function () {
    'use strict';

    var root = document.getElementById('killaGlobalSearch');
    if (!root) {
        return;
    }

    var input = document.getElementById('killaGlobalSearchInput');
    var panel = document.getElementById('killaGlobalSearchResults');
    var endpoint = root.getAttribute('data-endpoint');
    var fullUrl = root.getAttribute('data-full-url') || '';
    var i18n = {
        loading: root.getAttribute('data-i18n-loading') || 'Searching…',
        noResults: root.getAttribute('data-i18n-no-results') || 'No matches',
        error: root.getAttribute('data-i18n-error') || 'Search is unavailable right now.',
        hint: root.getAttribute('data-i18n-hint') || ''
    };

    /* icon + tint per result group, mirroring the header nav chips */
    var GROUP_META = {
        assets: { icon: 'fas fa-barcode', color: 'info' },
        licenses: { icon: 'far fa-save', color: 'licenses' },
        accessories: { icon: 'far fa-keyboard', color: 'accessories' },
        consumables: { icon: 'fas fa-tint', color: 'consumables' },
        components: { icon: 'far fa-hdd', color: 'components' },
        users: { icon: 'fas fa-users', color: 'users' },
        models: { icon: 'fa-solid fa-boxes-stacked', color: 'models' },
        categories: { icon: 'fa-solid fa-icons', color: 'categories' },
        locations: { icon: 'fas fa-map-marker-alt', color: 'locations' },
        companies: { icon: 'fa-regular fa-building', color: 'companies' },
        departments: { icon: 'fa-solid fa-building-user', color: 'departments' },
        manufacturers: { icon: 'fa-solid fa-industry', color: 'manufacturers' },
        suppliers: { icon: 'fa-solid fa-store', color: 'suppliers' },
        kits: { icon: 'fas fa-object-group', color: 'kits' }
    };

    var csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var debounceTimer = null;
    var activeRequest = null;
    var activeIndex = -1; /* keyboard highlight across all rendered results */

    function csrfHeader() {
        if (csrfToken) {
            return csrfToken;
        }
        var field = document.querySelector('input[name="_token"]');
        return field ? field.value : '';
    }

    function closePanel() {
        panel.hidden = true;
        panel.innerHTML = '';
        activeIndex = -1;
        input.setAttribute('aria-expanded', 'false');
    }

    function statusRow(text, className) {
        var row = document.createElement('div');
        row.className = 'kgs-status ' + (className || '');
        row.textContent = text;
        return row;
    }

    function resultItems() {
        return Array.prototype.slice.call(panel.querySelectorAll('.kgs-result'));
    }

    function highlight(items) {
        items.forEach(function (item, i) {
            item.classList.toggle('kgs-active', i === activeIndex);
        });
    }

    function render(groups, query) {
        panel.innerHTML = '';
        activeIndex = -1;

        if (!groups.length) {
            panel.appendChild(statusRow(i18n.noResults.replace(':query', query), 'kgs-empty'));
            panel.hidden = false;
            input.setAttribute('aria-expanded', 'true');
            return;
        }

        groups.forEach(function (group) {
            var meta = GROUP_META[group.key] || { icon: 'fas fa-circle', color: 'info' };

            var section = document.createElement('div');
            section.className = 'kgs-group';
            section.setAttribute('data-kgs-group', group.key);

            var header = document.createElement('div');
            header.className = 'kgs-group-label';

            var icon = document.createElement('i');
            icon.className = meta.icon;
            icon.setAttribute('aria-hidden', 'true');
            header.appendChild(icon);
            header.appendChild(document.createTextNode(group.label));
            section.appendChild(header);

            group.results.forEach(function (result) {
                var link = document.createElement('a');
                link.className = 'kgs-result';
                link.href = result.url;
                link.setAttribute('role', 'option');

                var iconSlot = document.createElement('span');
                iconSlot.className = 'kgs-result-icon kgs-tint-' + meta.color;
                var resultIcon = document.createElement('i');
                resultIcon.className = meta.icon;
                resultIcon.setAttribute('aria-hidden', 'true');
                iconSlot.appendChild(resultIcon);
                link.appendChild(iconSlot);

                var text = document.createElement('span');
                text.className = 'kgs-result-text';

                var title = document.createElement('span');
                title.className = 'kgs-result-title';
                title.textContent = result.title;
                text.appendChild(title);

                if (result.subtitle) {
                    var subtitle = document.createElement('span');
                    subtitle.className = 'kgs-result-subtitle';
                    subtitle.textContent = result.subtitle;
                    text.appendChild(subtitle);
                }

                /* KCP-074: condensed attached-items line on user results —
                   "3 devices: KLD-001, KLD-002 +1 more · 2 licenses" */
                if (result.attached) {
                    var a = result.attached;
                    var parts = [];
                    if (a.assets_count > 0) {
                        var sample = (a.assets_sample || []).join(', ');
                        var devices = a.assets_count + (a.assets_count === 1 ? ' device' : ' devices');
                        if (sample) {
                            devices += ': ' + sample + (a.assets_count > (a.assets_sample || []).length ? ' +' + (a.assets_count - a.assets_sample.length) + ' more' : '');
                        }
                        parts.push(devices);
                    }
                    if (a.licenses_count > 0) {
                        parts.push(a.licenses_count + (a.licenses_count === 1 ? ' license' : ' licenses'));
                    }
                    if (a.accessories_count > 0) {
                        parts.push(a.accessories_count + (a.accessories_count === 1 ? ' accessory' : ' accessories'));
                    }
                    if (a.consumables_count > 0) {
                        parts.push(a.consumables_count + (a.consumables_count === 1 ? ' consumable' : ' consumables'));
                    }
                    if (parts.length) {
                        var attached = document.createElement('span');
                        attached.className = 'kgs-result-attached';
                        attached.textContent = parts.join(' · ');
                        text.appendChild(attached);
                    }
                }

                link.appendChild(text);
                section.appendChild(link);
            });

            panel.appendChild(section);
        });

        if (i18n.hint) {
            panel.appendChild(statusRow(i18n.hint, 'kgs-hint'));
        }

        panel.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    }

    function search(query) {
        if (activeRequest) {
            activeRequest.abort();
        }

        if (query.length < 2) {
            closePanel();
            return;
        }

        panel.innerHTML = '';
        panel.appendChild(statusRow(i18n.loading, 'kgs-loading'));
        panel.hidden = false;

        activeRequest = new AbortController();

        fetch(endpoint + '?q=' + encodeURIComponent(query), {
            headers: {
                'X-CSRF-TOKEN': csrfHeader(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            signal: activeRequest.signal
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(function (payload) {
                render(payload.groups || [], query);
            })
            .catch(function (err) {
                if (err && err.name === 'AbortError') {
                    return;
                }
                panel.innerHTML = '';
                panel.appendChild(statusRow(i18n.error, 'kgs-empty'));
                panel.hidden = false;
            });
    }

    input.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        var query = input.value.trim();
        debounceTimer = setTimeout(function () {
            search(query);
        }, 200);
    });

    input.addEventListener('keydown', function (event) {
        var items = resultItems();

        if (event.key === 'Escape') {
            closePanel();
            input.blur();
            return;
        }

        if (!items.length) {
            return;
        }

        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            activeIndex = event.key === 'ArrowDown'
                ? (activeIndex + 1) % items.length
                : (activeIndex - 1 + items.length) % items.length;
            highlight(items);
            items[activeIndex].scrollIntoView({ block: 'nearest' });
            return;
        }

        if (event.key === 'Enter') {
            event.preventDefault();
            /* KCP-074: Enter on a highlighted row opens it; otherwise Enter
               opens the full results page for the current query. */
            if (activeIndex >= 0 && items[activeIndex]) {
                window.location.href = items[activeIndex].href;
                return;
            }
            var query = input.value.trim();
            if (fullUrl && query.length >= 2) {
                window.location.href = fullUrl + '?q=' + encodeURIComponent(query);
            }
        }
    });

    input.addEventListener('focus', function () {
        if (input.value.trim().length >= 2 && !panel.children.length) {
            search(input.value.trim());
        }
    });

    document.addEventListener('click', function (event) {
        if (!root.contains(event.target)) {
            closePanel();
        }
    });
})();
