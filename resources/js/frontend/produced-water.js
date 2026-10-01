import { Chart } from 'chart.js/auto';
import zoomPlugin from 'chartjs-plugin-zoom';
import 'chartjs-adapter-date-fns';

Chart.register(zoomPlugin);

document.addEventListener('DOMContentLoaded', () => {
    console.log('produced-water.js loaded');

    const root = document.getElementById('producedWaterFluxRoot');
    if (!root) {
        // Not on Produced Water page
        return;
    }

    const jsonUrl = root.dataset.jsonUrl;
    const columnsUrl = root.dataset.columnsUrl;

    const canvas = document.getElementById('emissionsChart');
    const picker = document.getElementById('colPicker');
    const smooth = document.getElementById('smooth');
    const smoothVal = document.getElementById('smoothVal');
    const toggleLog = document.getElementById('toggleLog');
    const logWarning = document.getElementById('logWarning');
    const toggleWind = document.getElementById('toggleWindCorrected');

    let chart = null;
    let currentSeries = [];
    let currentLabel = '';
    let currentUnits = '';

    function randColor() {
        return 'hsl(210 70% 45%)';
    }

    function formatDate(iso) {
      if (!iso) return null;

      const d = new Date(iso);

      return d.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
      });
    }

    function formatTime(iso) {
      if (!iso) return null;

      const d = new Date(iso);

      return d.toLocaleTimeString(undefined, {
        hour: '2-digit',
        minute: '2-digit',
      });
    }

    async function fetchColumns() {
        const res = await fetch(columnsUrl);
        return await res.json();
    }

    async function fetchSeries(yKey) {
        const params = new URLSearchParams({
            y: yKey,
            wind_corrected: toggleWind?.checked ? 1 : 0,
        });

        const res = await fetch(`${jsonUrl}?${params.toString()}`);
        return await res.json();
    }

    function buildPicker(columns) {
        picker.innerHTML = '';

        const select = document.createElement('select');
        select.className = 'form-select';
        select.id = 'yAxisSelect';

        Object.entries(columns).forEach(([key, label]) => {
            const opt = document.createElement('option');
            opt.value = key;
            opt.textContent = label;
            select.appendChild(opt);
        });

        select.addEventListener('change', () => loadAndRender(select.value));

        picker.appendChild(select);

        return select.value;
    }

    function render(series, label) {
        const tension = parseFloat(smooth.value);
        smoothVal.textContent = tension.toFixed(2);

        const useLog = toggleLog.checked;
        logWarning.classList.toggle('d-none', !useLog);

        const dataset = {
            label,
            data: series.map(p => ({ x: p.x, y: p.y })),
            borderColor: randColor(),
            backgroundColor: 'transparent',
            pointRadius: 2,
            pointHoverRadius: 4,
            tension,
            cubicInterpolationMode: 'monotone',
            spanGaps: true,
            parsing: false,
        };

        if (chart) {
            chart.destroy();
            chart = null;
        }


        chart = new Chart(canvas, {
            type: 'line',
            data: { datasets: [dataset] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'nearest', intersect: false },
                scales: {
                    x: {
                      type: 'linear',
                      title: { display: true, text: 'Sample index' },
                      ticks: {
                        precision: 0
                      }
                    },
                    y: {
                        type: useLog ? 'logarithmic' : 'linear',
                        title: {
                            display: true,
                            text: `${label} (${currentUnits})`,
                        },
                    },
                },
                plugins: {
                  tooltip: {
                    callbacks: {
                      afterLabel: ctx => {
                        const p = currentSeries[ctx.dataIndex];
                        const lines = [];

                        if (p.time) {
                          const date = formatDate(p.time);
                          const time = formatTime(p.time);

                          if (date) lines.push(`Date: ${date}`);
                          if (time) lines.push(`Time: ${time}`);
                        }

                        if (p.duration !== null && p.duration !== undefined) {
                          lines.push(`Duration: ${Math.round(p.duration)} min`);
                        }

                        return lines;
                      }
                    }
                  },
                  legend: { display: true },
                  zoom: {
                    zoom: {
                      wheel: { enabled: true },
                      pinch: { enabled: true },
                      mode: 'x',
                    },
                    pan: { enabled: true, mode: 'x' },
                  },
                },

            },
        });
    }

    async function loadAndRender(yKey) {
        const data = await fetchSeries(yKey);
        currentSeries = data.series;
        currentLabel = yKey;
        currentUnits = data.meta.units;
        render(currentSeries, yKey);
    }

    smooth.addEventListener('input', () => chart && render(currentSeries, currentLabel));
    toggleLog.addEventListener('change', () => chart && render(currentSeries, currentLabel));
    toggleWind?.addEventListener('change', () => {
        const sel = document.getElementById('yAxisSelect');
        if (sel) loadAndRender(sel.value);
    });

    document.getElementById('btnResetZoom')?.addEventListener('click', () => chart?.resetZoom());

    document.getElementById('btnPng')?.addEventListener('click', () => {
        if (!chart) return;
        const a = document.createElement('a');
        a.href = chart.toBase64Image('image/png', 1);
        a.download = 'produced_water_flux.png';
        a.click();
    });

    document.getElementById('btnCsv')?.addEventListener('click', () => {
        if (!currentSeries.length) return;

        const rows = [['datetime', currentLabel]];
        currentSeries.forEach(p => rows.push([p.x, p.y ?? '']));

        const csv = rows.map(r => r.join(',')).join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'produced_water_flux.csv';
        a.click();
        URL.revokeObjectURL(url);
    });

    (async function init() {
        const meta = await fetchColumns();
        const firstKey = buildPicker(meta.columns);
        currentUnits = meta.units;
        await loadAndRender(firstKey);
    })();
});
