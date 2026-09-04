(function () {
    const soundsOn = document.documentElement.getAttribute('data-sounds') === '1';

    function beep(freq, ms) {
        if (!soundsOn || !window.AudioContext) return;
        try {
            const ctx = new AudioContext();
            const o = ctx.createOscillator();
            const g = ctx.createGain();
            o.type = 'square';
            o.frequency.value = freq;
            g.gain.value = 0.03;
            o.connect(g);
            g.connect(ctx.destination);
            o.start();
            setTimeout(() => { o.stop(); ctx.close(); }, ms || 40);
        } catch (e) { /* ignore */ }
    }

    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('is-open');
            if (backdrop) backdrop.hidden = !sidebar.classList.contains('is-open');
            beep(220, 30);
        });
        if (backdrop) {
            backdrop.addEventListener('click', () => {
                sidebar.classList.remove('is-open');
                backdrop.hidden = true;
            });
        }
    }

    const drawer = document.getElementById('eventDrawer');
    const mask = document.getElementById('drawerMask');
    function closeDrawer() {
        if (!drawer) return;
        drawer.classList.remove('is-open');
        if (mask) mask.classList.remove('is-open');
        drawer.setAttribute('aria-hidden', 'true');
    }
    function openDrawer() {
        if (!drawer) return;
        drawer.classList.add('is-open');
        if (mask) mask.classList.add('is-open');
        drawer.setAttribute('aria-hidden', 'false');
        const title = drawer.querySelector('[name="title"]');
        if (title) title.focus();
        beep(330, 40);
    }
    const closeBtn = document.getElementById('closeDrawer');
    if (closeBtn) closeBtn.addEventListener('click', (ev) => {
        if (drawer && drawer.classList.contains('is-open') && !new URLSearchParams(location.search).has('evento') && !new URLSearchParams(location.search).has('nuevo')) {
            ev.preventDefault();
            closeDrawer();
        }
    });
    if (mask) mask.addEventListener('click', () => {
        const params = new URLSearchParams(location.search);
        if (params.has('evento') || params.has('nuevo')) {
            location.href = location.pathname + location.search.replace(/([?&])(evento|nuevo)=[^&]*/g, '').replace(/\?$/, '');
            return;
        }
        closeDrawer();
    });

    const palette = document.getElementById('palette');
    const paletteInput = document.getElementById('paletteInput');
    const paletteList = document.getElementById('paletteList');
    const tk = window.TK || { urls: {}, archives: [], timelines: [] };

    function commands() {
        const items = [
            { label: 'Dashboard', url: tk.urls.home },
            { label: 'Crear archivo', url: tk.urls.archiveNew },
            { label: 'Crear línea', url: tk.urls.timelineNew },
            { label: 'Buscar', url: tk.urls.search },
            { label: 'Métricas', url: tk.urls.metrics },
            { label: 'Favoritos', url: tk.urls.favorites },
            { label: 'Papelera', url: tk.urls.trash },
            { label: 'Ajustes', url: tk.urls.settings }
        ];
        (tk.archives || []).forEach((a) => items.push({ label: 'Archivo · ' + a.name, url: tk.archiveUrl + a.id }));
        (tk.timelines || []).forEach((t) => items.push({ label: 'Línea · ' + t.name, url: tk.timelineUrl + t.id }));
        return items;
    }

    let paletteIndex = 0;
    let paletteItems = [];

    function renderPalette(q) {
        const query = (q || '').toLowerCase();
        paletteItems = commands().filter((c) => c.label.toLowerCase().includes(query)).slice(0, 12);
        paletteIndex = 0;
        if (!paletteList) return;
        paletteList.innerHTML = paletteItems.map((c, i) => '<li data-i="' + i + '"' + (i === 0 ? ' class="is-on"' : '') + '>' + c.label + '</li>').join('');
    }

    function openPalette() {
        if (!palette) return;
        palette.hidden = false;
        palette.classList.add('is-open');
        renderPalette('');
        if (paletteInput) {
            paletteInput.value = '';
            paletteInput.focus();
        }
        beep(440, 30);
    }
    function closePalette() {
        if (!palette) return;
        palette.classList.remove('is-open');
        palette.hidden = true;
    }

    const paletteBtn = document.getElementById('paletteBtn');
    const paletteClose = document.getElementById('paletteClose');
    if (paletteBtn) paletteBtn.addEventListener('click', openPalette);
    if (paletteClose) paletteClose.addEventListener('click', closePalette);
    if (palette) {
        palette.addEventListener('click', (e) => {
            if (e.target === palette) closePalette();
        });
    }
    if (paletteInput) {
        paletteInput.addEventListener('input', () => renderPalette(paletteInput.value));
        paletteInput.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown') { e.preventDefault(); paletteIndex = Math.min(paletteIndex + 1, paletteItems.length - 1); }
            if (e.key === 'ArrowUp') { e.preventDefault(); paletteIndex = Math.max(paletteIndex - 1, 0); }
            if (e.key === 'Enter' && paletteItems[paletteIndex]) {
                location.href = paletteItems[paletteIndex].url;
            }
            Array.from(paletteList.children).forEach((li, i) => li.classList.toggle('is-on', i === paletteIndex));
        });
    }
    if (paletteList) {
        paletteList.addEventListener('click', (e) => {
            const li = e.target.closest('li');
            if (!li) return;
            const item = paletteItems[Number(li.getAttribute('data-i'))];
            if (item) location.href = item.url;
        });
    }

    document.addEventListener('keydown', (e) => {
        const tag = (e.target && e.target.tagName) || '';
        const typing = tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT';
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            openPalette();
            return;
        }
        if (e.key === 'Escape') {
            closePalette();
            closeDrawer();
            return;
        }
        if (typing) return;
        if (e.key.toLowerCase() === 'f') {
            const search = document.querySelector('.top-search input');
            if (search) { e.preventDefault(); search.focus(); }
        }
        if (e.key.toLowerCase() === 'n' && drawer) {
            e.preventDefault();
            const btn = document.getElementById('newEventBtn');
            if (btn) { location.href = btn.href; return; }
            openDrawer();
        }
        if (e.key.toLowerCase() === 'v') {
            const a = document.querySelector('.view-toggle a[href*="vista=vertical"]');
            if (a) location.href = a.href;
        }
        if (e.key.toLowerCase() === 'h') {
            const a = document.querySelector('.view-toggle a[href*="vista=horizontal"]');
            if (a) location.href = a.href;
        }
        if (e.key.toLowerCase() === 'g') {
            const goto = document.getElementById('hGoto');
            if (goto) { e.preventDefault(); goto.focus(); }
        }
    });

    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', () => beep(520, 50));
    });

    function bulkBoxes(form) {
        const inside = Array.from(form.querySelectorAll('input[name="ids[]"]'));
        if (!form.id) {
            return inside;
        }
        const outside = Array.from(document.querySelectorAll('input[name="ids[]"][form="' + form.id + '"]'));
        return inside.concat(outside);
    }

    function bulkSubmitBtn(form) {
        const inside = form.querySelector('[data-bulk-submit]');
        if (inside) {
            return inside;
        }
        if (!form.id) {
            return null;
        }
        return document.querySelector('[data-bulk-submit][form="' + form.id + '"]');
    }

    function syncBulk(form) {
        const boxes = bulkBoxes(form);
        const checked = boxes.filter((box) => box.checked);
        const submit = bulkSubmitBtn(form);
        const master = form.querySelector('[data-select-all]');
        if (submit) submit.disabled = checked.length === 0;
        if (master && boxes.length) {
            master.checked = checked.length === boxes.length;
            master.indeterminate = checked.length > 0 && checked.length < boxes.length;
        }
    }

    document.addEventListener('click', (e) => {
        document.querySelectorAll('details.drop[open]').forEach((drop) => {
            if (!drop.contains(e.target)) {
                drop.removeAttribute('open');
            }
        });
    });
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('details.drop[open]').forEach((drop) => drop.removeAttribute('open'));
    });

    document.querySelectorAll('form[data-series-form]').forEach((form) => {
        const archive = form.querySelector('[name="collection_id"]');
        const series = form.querySelector('[name="series_id"]');
        const wrap = form.querySelector('[data-series-wrap]');
        if (!archive || !series || !wrap) return;

        function syncSeries() {
            const cid = archive.value;
            wrap.hidden = !cid;
            wrap.style.display = cid ? '' : 'none';
            series.querySelectorAll('option[data-archive]').forEach((opt) => {
                const show = cid !== '' && opt.getAttribute('data-archive') === cid;
                opt.hidden = !show;
                opt.disabled = !show;
            });
            const selected = series.selectedOptions[0];
            if (!cid || (selected && selected.disabled)) {
                series.value = '';
            }
        }

        archive.addEventListener('change', syncSeries);
        syncSeries();
    });

    document.querySelectorAll('form[data-bulk]').forEach((form) => {
        syncBulk(form);
        form.addEventListener('change', (e) => {
            if (e.target && e.target.classList && e.target.classList.contains('serie-pick')) {
                return;
            }
            syncBulk(form);
        });
        const master = form.querySelector('[data-select-all]');
        if (master) {
            master.addEventListener('change', () => {
                bulkBoxes(form).forEach((box) => {
                    box.checked = master.checked;
                });
                syncBulk(form);
            });
        }
        form.addEventListener('submit', (e) => {
            const n = bulkBoxes(form).filter((box) => box.checked).length;
            if (n < 1) {
                e.preventDefault();
                return;
            }
            const msg = form.getAttribute('data-bulk-confirm') || ('¿Eliminar ' + n + ' seleccionados?');
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    document.addEventListener('change', (e) => {
        const target = e.target;
        if (!target || target.name !== 'ids[]') {
            return;
        }
        const formId = target.getAttribute('form');
        if (!formId) {
            return;
        }
        const owner = document.getElementById(formId);
        if (owner && owner.hasAttribute('data-bulk')) {
            syncBulk(owner);
        }
    });
})();
