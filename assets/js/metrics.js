(function () {
    const node = document.getElementById('metrics-data');
    if (!node || typeof Chart === 'undefined') return;

    let data = {};
    try {
        data = JSON.parse(node.textContent || '{}');
    } catch (e) {
        return;
    }

    const ink = '#E1DED5';
    const muted = '#AAA7A0';
    const grid = '#4A4956';
    const pastels = ['#91A7C4', '#C9B58A', '#A99BC2', '#9FB59D', '#C3909B', '#B78972', '#C77979', '#AAA7A0'];

    Chart.defaults.color = muted;
    Chart.defaults.borderColor = grid;
    Chart.defaults.font.family = '"IBM Plex Mono", monospace';
    Chart.defaults.font.size = 11;
    Chart.defaults.plugins.legend.labels.boxWidth = 10;
    Chart.defaults.plugins.legend.labels.color = ink;
    Chart.defaults.elements.bar.borderRadius = 0;
    Chart.defaults.elements.line.tension = 0;
    Chart.defaults.elements.point.radius = 3;

    function nums(rows, key) {
        return (rows || []).map((r) => Number(r[key] || 0));
    }
    function labels(rows) {
        return (rows || []).map((r) => String(r.label || ''));
    }
    function colors(rows) {
        return (rows || []).map((r, i) => r.color || pastels[i % pastels.length]);
    }
    function empty(canvasId, message) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return true;
        const wrap = canvas.parentElement;
        if (!wrap) return true;
        const p = document.createElement('p');
        p.className = 'empty';
        p.textContent = message;
        wrap.replaceChild(p, canvas);
        return true;
    }

    const pieOpts = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } }
    };

    const archive = data.byArchive || [];
    if (archive.length && nums(archive, 'total').some((n) => n > 0)) {
        new Chart(document.getElementById('chartPie'), {
            type: 'pie',
            data: {
                labels: labels(archive),
                datasets: [{ data: nums(archive, 'total'), backgroundColor: colors(archive), borderColor: '#23232E', borderWidth: 2 }]
            },
            options: pieOpts
        });
    } else {
        empty('chartPie', 'Todavía no hay eventos para armar la torta.');
    }

    const precision = data.byPrecision || [];
    if (precision.length) {
        new Chart(document.getElementById('chartPrecision'), {
            type: 'doughnut',
            data: {
                labels: labels(precision),
                datasets: [{ data: nums(precision, 'total'), backgroundColor: pastels, borderColor: '#23232E', borderWidth: 2 }]
            },
            options: pieOpts
        });
    } else {
        empty('chartPrecision', 'Sin fechas cargadas.');
    }

    const timelines = data.byTimeline || [];
    if (timelines.length) {
        new Chart(document.getElementById('chartBars'), {
            type: 'bar',
            data: {
                labels: labels(timelines),
                datasets: [{
                    label: 'Eventos',
                    data: nums(timelines, 'total'),
                    backgroundColor: colors(timelines),
                    borderColor: '#111118',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { maxRotation: 45, minRotation: 0, color: muted }, grid: { display: false } },
                    y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: grid } }
                }
            }
        });
    } else {
        empty('chartBars', 'No hay líneas todavía.');
    }

    const years = data.byYear || [];
    if (years.length) {
        new Chart(document.getElementById('chartLine'), {
            type: 'line',
            data: {
                labels: labels(years),
                datasets: [{
                    label: 'Eventos',
                    data: nums(years, 'total'),
                    borderColor: '#C9B58A',
                    backgroundColor: 'rgba(201,181,138,.18)',
                    fill: true,
                    pointBackgroundColor: '#C9B58A',
                    pointBorderColor: '#181821',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: grid } },
                    y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: grid } }
                }
            }
        });
    } else {
        empty('chartLine', 'No hay eventos con año para la línea temporal.');
    }

    const composition = data.composition || [];
    if (composition.length) {
        new Chart(document.getElementById('chartStack'), {
            type: 'bar',
            data: {
                labels: labels(composition),
                datasets: [
                    { label: 'Regulares', data: nums(composition, 'regulars'), backgroundColor: '#91A7C4', stack: 'comp' },
                    { label: 'Hitos', data: nums(composition, 'milestones'), backgroundColor: '#C9B58A', stack: 'comp' },
                    { label: 'En curso', data: nums(composition, 'ongoing'), backgroundColor: '#9FB59D', stack: 'comp' }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    x: { stacked: true, grid: { display: false } },
                    y: { stacked: true, beginAtZero: true, ticks: { precision: 0 }, grid: { color: grid } }
                }
            }
        });
    } else {
        empty('chartStack', 'No hay décadas para componer.');
    }

    const status = data.byStatus || [];
    if (status.length) {
        new Chart(document.getElementById('chartStatus'), {
            type: 'pie',
            data: {
                labels: labels(status),
                datasets: [{
                    data: nums(status, 'total'),
                    backgroundColor: ['#9FB59D', '#C9B58A', '#B78972'],
                    borderColor: '#23232E',
                    borderWidth: 2
                }]
            },
            options: pieOpts
        });
    } else {
        empty('chartStatus', 'No hay líneas.');
    }
})();
