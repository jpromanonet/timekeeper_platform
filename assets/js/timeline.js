(function () {
    const board = document.getElementById('horizontalBoard');
    if (!board) return;

    let events = [];
    try { events = JSON.parse(board.getAttribute('data-events') || '[]'); } catch (e) { events = []; }
    events = events.filter((ev) => ev.start);

    const times = events.map((ev) => new Date(ev.start + 'T00:00:00').getTime());
    const minT = times.length ? Math.min.apply(null, times) : Date.now() - 3.15e10;
    const maxT = times.length ? Math.max.apply(null, times) : Date.now();
    const pad = Math.max((maxT - minT) * 0.08, 86400000 * 30);
    const rangeStart = minT - pad;
    const rangeEnd = maxT + pad;

    let viewStart = rangeStart;
    let viewEnd = rangeEnd;
    let drag = null;

    const levels = [
        { name: 'SIGLOS', span: 86400000 * 365 * 80 },
        { name: 'DÉCADAS', span: 86400000 * 365 * 12 },
        { name: 'AÑOS', span: 86400000 * 365 * 2 },
        { name: 'MESES', span: 86400000 * 80 },
        { name: 'DÍAS', span: 86400000 * 12 }
    ];

    function levelName() {
        const span = viewEnd - viewStart;
        for (let i = 0; i < levels.length; i++) {
            if (span >= levels[i].span) return levels[i].name;
        }
        return 'DÍAS';
    }

    function xOf(t) {
        const w = board.clientWidth || 1;
        return ((t - viewStart) / (viewEnd - viewStart)) * w;
    }

    function ticks() {
        const span = viewEnd - viewStart;
        const name = levelName();
        const out = [];
        const start = new Date(viewStart);
        start.setHours(0, 0, 0, 0);
        if (name === 'SIGLOS') {
            const y = Math.floor(start.getFullYear() / 100) * 100;
            for (let year = y; year <= new Date(viewEnd).getFullYear() + 100; year += 100) {
                out.push({ t: new Date(year, 0, 1).getTime(), label: String(year) });
            }
        } else if (name === 'DÉCADAS') {
            const y = Math.floor(start.getFullYear() / 10) * 10;
            for (let year = y; year <= new Date(viewEnd).getFullYear() + 10; year += 10) {
                out.push({ t: new Date(year, 0, 1).getTime(), label: String(year) });
            }
        } else if (name === 'AÑOS') {
            for (let year = start.getFullYear(); year <= new Date(viewEnd).getFullYear() + 1; year++) {
                out.push({ t: new Date(year, 0, 1).getTime(), label: String(year) });
            }
        } else if (name === 'MESES') {
            const d = new Date(start.getFullYear(), start.getMonth(), 1);
            while (d.getTime() < viewEnd + 2.6e9) {
                out.push({ t: d.getTime(), label: d.toLocaleString('es', { month: 'short', year: '2-digit' }) });
                d.setMonth(d.getMonth() + 1);
            }
        } else {
            const d = new Date(start);
            while (d.getTime() < viewEnd + 86400000) {
                out.push({ t: d.getTime(), label: String(d.getDate()) });
                d.setDate(d.getDate() + 1);
            }
        }
        return out.filter((tk) => tk.t >= viewStart - span * 0.1 && tk.t <= viewEnd + span * 0.1);
    }

    function render() {
        const label = document.getElementById('hZoomLabel');
        if (label) label.textContent = levelName();
        const w = board.clientWidth || 800;
        let html = '<div class="tl-h-axis" style="width:' + w + 'px"></div>';
        ticks().forEach((tk) => {
            html += '<div class="tl-h-tick" style="left:' + xOf(tk.t) + 'px">' + tk.label + '</div>';
        });
        let lane = 0;
        events.forEach((ev) => {
            const t = new Date(ev.start + 'T00:00:00').getTime();
            const left = xOf(t);
            if (left < -200 || left > w + 40) return;
            const top = 90 + (lane % 3) * 96;
            lane += 1;
            html += '<div class="tl-h-dot" style="left:' + (left - 4) + 'px"></div>';
            html += '<a class="tl-h-event' + (ev.is_milestone ? ' is-mile' : '') + '" href="' + ev.url + '" style="left:' + left + 'px;top:' + top + 'px;--cat:' + (ev.color || '#C9B58A') + '">';
            html += '<small>' + (ev.label || '') + '</small><strong>' + ev.title + '</strong>';
            html += '</a>';
        });
        board.innerHTML = html;
        renderMap();
    }

    function renderMap() {
        const map = document.getElementById('minimap');
        if (!map) return;
        const full = rangeEnd - rangeStart;
        const left = ((viewStart - rangeStart) / full) * 100;
        const width = ((viewEnd - viewStart) / full) * 100;
        map.innerHTML = '<div class="win" style="left:' + Math.max(0, left) + '%;width:' + Math.max(4, width) + '%"></div>';
    }

    function zoom(dir) {
        const mid = (viewStart + viewEnd) / 2;
        const span = viewEnd - viewStart;
        const next = dir > 0 ? span / 1.6 : span * 1.6;
        const minSpan = 86400000 * 7;
        const maxSpan = (rangeEnd - rangeStart) * 1.4;
        const use = Math.min(maxSpan, Math.max(minSpan, next));
        viewStart = mid - use / 2;
        viewEnd = mid + use / 2;
        render();
    }

    function pan(dx) {
        const w = board.clientWidth || 1;
        const delta = (dx / w) * (viewEnd - viewStart);
        viewStart -= delta;
        viewEnd -= delta;
        render();
    }

    board.addEventListener('pointerdown', (e) => {
        drag = { x: e.clientX };
        board.classList.add('is-drag');
        board.setPointerCapture(e.pointerId);
    });
    board.addEventListener('pointermove', (e) => {
        if (!drag) return;
        pan(e.clientX - drag.x);
        drag.x = e.clientX;
    });
    board.addEventListener('pointerup', () => { drag = null; board.classList.remove('is-drag'); });
    board.addEventListener('wheel', (e) => {
        e.preventDefault();
        zoom(e.deltaY < 0 ? 1 : -1);
    }, { passive: false });

    document.querySelectorAll('[data-h-zoom]').forEach((btn) => {
        btn.addEventListener('click', () => zoom(Number(btn.getAttribute('data-h-zoom'))));
    });
    const center = document.querySelector('[data-h-center]');
    if (center) center.addEventListener('click', () => { viewStart = rangeStart; viewEnd = rangeEnd; render(); });

    const goto = document.getElementById('hGoto');
    if (goto) {
        goto.addEventListener('keydown', (e) => {
            if (e.key !== 'Enter') return;
            const n = parseInt(goto.value, 10);
            if (!n) return;
            const t = new Date(n, 0, 1).getTime();
            const span = viewEnd - viewStart;
            viewStart = t - span / 2;
            viewEnd = t + span / 2;
            render();
        });
    }

    const map = document.getElementById('minimap');
    if (map) {
        map.addEventListener('click', (e) => {
            const rect = map.getBoundingClientRect();
            const ratio = (e.clientX - rect.left) / rect.width;
            const t = rangeStart + ratio * (rangeEnd - rangeStart);
            const span = viewEnd - viewStart;
            viewStart = t - span / 2;
            viewEnd = t + span / 2;
            render();
        });
    }

    window.addEventListener('resize', render);
    render();
})();
