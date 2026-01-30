import { Chart } from 'chart.js/auto';
import zoomPlugin from 'chartjs-plugin-zoom';

Chart.register(zoomPlugin);

const root = document.getElementById('emissionTrendsChartRoot');
if (!root) {
  // Not on this page
} else {
  const jsonUrl = root.dataset.jsonUrl;

  let chart;
  let availableCols = [];
  let currentData = null;      // raw data from server
  let axisByKey = {};          // { seriesKey: 'y' | 'y1' }

  // Controls
  const canvas = document.getElementById('emissionsChart');
  const picker = document.getElementById('colPicker');
  const smooth = document.getElementById('smooth');
  const smoothVal = document.getElementById('smoothVal');
  const toggleNormalize = document.getElementById('toggleNormalize');
  const toggleLog = document.getElementById('toggleLog');
  const logWarning = document.getElementById('logWarning');

  const yearMin = document.getElementById('yearMin');
  const yearMax = document.getElementById('yearMax');
  const yearMinLabel = document.getElementById('yearMinLabel');
  const yearMaxLabel = document.getElementById('yearMaxLabel');

  function randColor(i) {
    const hue = (i * 67) % 360;
    return `hsl(${hue} 70% 45%)`;
  }

  function defaultAxisForKey(key) {
    // heuristic: prices on right axis by default
    const k = key.toLowerCase();
    if (k.includes('price') || k.includes('doll')) return 'y1';
    return 'y';
  }

  function buildPicker(selectedKeys) {
    picker.innerHTML = '';

    const wrap = document.createElement('div');
    wrap.className = 'd-flex flex-column gap-2';

    availableCols.forEach((c) => {
      const id = `col_${c.key}`;
      const row = document.createElement('div');
      row.className = 'row align-items-center gx-2 mb-1';

      row.innerHTML = `
        <div class="col-8">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="${id}" value="${c.key}">
            <label class="form-check-label" for="${id}">
              ${c.label}
            </label>
          </div>
        </div>

        <div class="col-4">
          <select class="form-select form-select-sm axis-select" data-key="${c.key}">
            <option value="y">Left</option>
            <option value="y1">Right</option>
          </select>
        </div>
      `;


      wrap.appendChild(row);

      const cb = row.querySelector(`#${CSS.escape(id)}`);
      cb.checked = selectedKeys.includes(c.key);

      // default axis
      if (!axisByKey[c.key]) axisByKey[c.key] = defaultAxisForKey(c.key);

      const axisSelect = row.querySelector('.axis-select');
      axisSelect.value = axisByKey[c.key];

      cb.addEventListener('change', () => loadAndRender(getSelectedCols()));
      axisSelect.addEventListener('change', (e) => {
        axisByKey[c.key] = e.target.value;
        // re-render without fetching
        if (currentData) render(currentData);
      });
    });

    picker.appendChild(wrap);
  }

  function getSelectedCols() {
    return Array.from(picker.querySelectorAll('input[type="checkbox"]'))
      .filter(cb => cb.checked)
      .map(cb => cb.value);
  }

  function toCsv(labels, series) {
    const header = ['year', ...series.map(s => s.label)];
    const rows = labels.map((year, idx) => {
      const vals = series.map(s => (s.data[idx] ?? ''));
      return [year, ...vals];
    });
    return [header, ...rows].map(r => r.join(',')).join('\n');
  }

  async function fetchData(cols) {
    const params = new URLSearchParams();
    cols.forEach(c => params.append('cols[]', c));
    const res = await fetch(`${jsonUrl}?${params.toString()}`, {
      headers: { Accept: 'application/json' }
    });
    return await res.json();
  }

  function clampRange() {
    let minV = parseInt(yearMin.value, 10);
    let maxV = parseInt(yearMax.value, 10);
    if (minV > maxV) {
      // swap
      const t = minV; minV = maxV; maxV = t;
      yearMin.value = minV;
      yearMax.value = maxV;
    }
    yearMinLabel.textContent = String(minV);
    yearMaxLabel.textContent = String(maxV);
    return { minV, maxV };
  }

  function applyYearFilter(labels, seriesArr) {
    const { minV, maxV } = clampRange();
    const idxs = labels
      .map((y, i) => ({ y, i }))
      .filter(o => o.y >= minV && o.y <= maxV)
      .map(o => o.i);

    const outLabels = idxs.map(i => labels[i]);
    const outSeries = seriesArr.map(s => ({
      ...s,
      data: idxs.map(i => s.data[i])
    }));

    return { outLabels, outSeries };
  }

  function normalizeSeries(labels, seriesArr) {
    // Index each series so first non-null in range becomes 100
    return seriesArr.map(s => {
      let base = null;
      for (let i = 0; i < s.data.length; i++) {
        const v = s.data[i];
        if (v !== null && v !== undefined && v !== '') {
          base = Number(v);
          if (!Number.isNaN(base)) break;
        }
      }
      if (base === null || base === 0 || Number.isNaN(base)) {
        return s; // can't normalize
      }
      return {
        ...s,
        data: s.data.map(v => {
          if (v === null || v === undefined || v === '') return null;
          const n = Number(v);
          if (Number.isNaN(n)) return null;
          return (n / base) * 100;
        })
      };
    });
  }

  function applyLogFilter(seriesArr) {
    // For log scale, values must be > 0
    return seriesArr.map(s => ({
      ...s,
      data: s.data.map(v => {
        if (v === null || v === undefined || v === '') return null;
        const n = Number(v);
        if (!Number.isFinite(n) || n <= 0) return null;
        return n;
      })
    }));
  }

  function render(data) {
    currentData = data;

    // controls
    const tension = parseFloat(smooth.value);
    smoothVal.textContent = tension.toFixed(2);

    // Start from raw server data
    let labels = [...data.labels];
    let seriesArr = data.series.map(s => ({ ...s, data: [...s.data] }));

    // Year range filter
    ({ outLabels: labels, outSeries: seriesArr } = applyYearFilter(labels, seriesArr));

    // Normalize (trend compare)
    if (toggleNormalize.checked) {
      seriesArr = normalizeSeries(labels, seriesArr);
    }

    // Log scale
    const useLog = toggleLog.checked;
    logWarning.classList.toggle('d-none', !useLog);
    if (useLog) {
      seriesArr = applyLogFilter(seriesArr);
    }

    // Dual axis + datasets
    const datasets = seriesArr.map((s, i) => ({
      label: s.label,
      yAxisID: axisByKey[s.key] || defaultAxisForKey(s.key),
      data: labels.map((x, idx) => ({ x, y: s.data[idx] })),
      parsing: false,
      borderColor: randColor(i),
      backgroundColor: 'transparent',
      tension,
      cubicInterpolationMode: 'monotone',
      spanGaps: true,
      pointRadius: 2,
      pointHoverRadius: 4,
    }));

    if (chart) chart.destroy();

    chart = new Chart(canvas, {
      type: 'line',
      data: { datasets },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'nearest', intersect: false },
        scales: {
          x: {
            type: 'linear',
            title: { display: true, text: 'Year' },
            ticks: { precision: 0 }
          },
          y: {
            type: useLog ? 'logarithmic' : 'linear',
            position: 'left',
            title: { display: true, text: 'Left axis' }
          },
          y1: {
            type: useLog ? 'logarithmic' : 'linear',
            position: 'right',
            title: { display: true, text: 'Right axis' },
            grid: { drawOnChartArea: false }
          }
        },
        plugins: {
          legend: { display: true },
          zoom: {
            zoom: { wheel: { enabled: true }, pinch: { enabled: true }, mode: 'x' },
            pan: { enabled: true, mode: 'x' },
          }
        }
      }
    });
  }

  async function loadAndRender(cols) {
    const data = await fetchData(cols);

    if (availableCols.length === 0) {
      availableCols = data.available;

      // init year sliders from full dataset
      const years = data.labels.map(y => Number(y)).filter(y => Number.isFinite(y));
      const minY = Math.min(...years);
      const maxY = Math.max(...years);

      yearMin.min = String(minY);
      yearMin.max = String(maxY);
      yearMax.min = String(minY);
      yearMax.max = String(maxY);

      yearMin.value = String(minY);
      yearMax.value = String(maxY);

      yearMinLabel.textContent = String(minY);
      yearMaxLabel.textContent = String(maxY);

      // picker + default selection from server
      buildPicker(data.series.map(s => s.key));
    }

    render(data);
  }

  // Events
  document.getElementById('btnResetZoom').addEventListener('click', () => chart?.resetZoom());
  smooth.addEventListener('input', () => {
    const tension = parseFloat(smooth.value);
    smoothVal.textContent = tension.toFixed(2);

    if (!chart) return;

    chart.data.datasets.forEach(ds => {
      ds.tension = tension;
      // make sure monotone isn't locking the curve
      delete ds.cubicInterpolationMode;
    });

    chart.update();
  });

  toggleNormalize.addEventListener('change', () => currentData && render(currentData));
  toggleLog.addEventListener('change', () => currentData && render(currentData));
  yearMin.addEventListener('input', () => currentData && render(currentData));
  yearMax.addEventListener('input', () => currentData && render(currentData));

  document.getElementById('btnPng').addEventListener('click', () => {
    if (!chart) return;
    const a = document.createElement('a');
    a.href = chart.toBase64Image('image/png', 1);
    a.download = 'emission_trends.png';
    a.click();
  });

  document.getElementById('btnCsv').addEventListener('click', () => {
    if (!currentData) return;

    // Use the SAME transformations as the chart (year filter, normalize, log filter)
    let labels = [...currentData.labels];
    let seriesArr = currentData.series.map(s => ({ ...s, data: [...s.data] }));

    ({ outLabels: labels, outSeries: seriesArr } = applyYearFilter(labels, seriesArr));
    if (toggleNormalize.checked) seriesArr = normalizeSeries(labels, seriesArr);
    if (toggleLog.checked) seriesArr = applyLogFilter(seriesArr);

    const csv = toCsv(labels, seriesArr);
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'emission_trends.csv';
    a.click();
    URL.revokeObjectURL(url);
  });

  // Initial load
  loadAndRender([]);
}
