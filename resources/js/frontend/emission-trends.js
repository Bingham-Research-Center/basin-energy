import { Chart } from 'chart.js/auto';
import zoomPlugin from 'chartjs-plugin-zoom';

Chart.register(zoomPlugin);

const root = document.getElementById('emissionTrendsChartRoot');

if (root) {
    const jsonUrl = root.dataset.jsonUrl;

    let chart;
    let availableCols = [];
    let currentData = null;
    let axisByKey = {};

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

    function isDarkTheme() {
        return document.body.classList.contains('c-dark-theme');
    }

    function chartTextColor() {
        return isDarkTheme() ? '#cbd5e1' : '#374151';
    }

    function chartMutedColor() {
        return isDarkTheme() ? '#94a3b8' : '#6b7280';
    }

    function chartGridColor() {
        return isDarkTheme()
            ? 'rgba(148, 163, 184, 0.14)'
            : 'rgba(107, 114, 128, 0.18)';
    }

    function defaultAxisForKey(key) {
        const k = key.toLowerCase();

        if (k.includes('price') || k.includes('doll')) {
            return 'y1';
        }

        return 'y';
    }

    function buildPicker(selectedKeys) {
        picker.innerHTML = '';

        const wrap = document.createElement('div');
        wrap.className = 'd-flex flex-column';

        availableCols.forEach((c) => {
            const id = `col_${c.key}`;
            const row = document.createElement('div');

            row.className = 'row align-items-center mb-1';

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
                    <select class="form-control form-control-sm axis-select" data-key="${c.key}">
                        <option value="y">Left</option>
                        <option value="y1">Right</option>
                    </select>
                </div>
            `;

            wrap.appendChild(row);

            const cb = row.querySelector(`#${CSS.escape(id)}`);
            cb.checked = selectedKeys.includes(c.key);

            if (!axisByKey[c.key]) {
                axisByKey[c.key] = defaultAxisForKey(c.key);
            }

            const axisSelect = row.querySelector('.axis-select');
            axisSelect.value = axisByKey[c.key];

            cb.addEventListener('change', () => loadAndRender(getSelectedCols()));

            axisSelect.addEventListener('change', (event) => {
                axisByKey[c.key] = event.target.value;

                if (currentData) {
                    render(currentData);
                }
            });
        });

        picker.appendChild(wrap);
    }

    function updateEmissionAiSummary(labels, seriesArr) {
        const el = document.getElementById('emissionAiSummary');

        if (!el) {
            return;
        }

        if (!labels.length || !seriesArr.length) {
            el.textContent = 'No emission trend data is currently selected. Choose one or more columns to summarize the trend.';
            return;
        }

        const summaries = seriesArr.slice(0, 3).map((series) => {
            const clean = series.data
                .map((value, index) => ({
                    year: labels[index],
                    value: Number(value),
                }))
                .filter((point) => Number.isFinite(point.value));

            if (clean.length < 2) {
                return `${series.label} does not have enough valid values for a trend estimate`;
            }

            const first = clean[0];
            const last = clean[clean.length - 1];
            const change = last.value - first.value;
            const pct = first.value !== 0 ? (change / first.value) * 100 : null;
            const direction = change > 0 ? 'increased' : change < 0 ? 'decreased' : 'remained nearly unchanged';

            const pctText = pct === null
                ? ''
                : ` by about ${Math.abs(pct).toFixed(1)}%`;

            return `${series.label} ${direction}${pctText} from ${first.year} to ${last.year}`;
        });

        el.textContent = `The selected emission trend view shows that ${summaries.join('; ')}. Use normalization to compare relative changes across variables with different units.`;
    }

    function getSelectedCols() {
        return Array.from(picker.querySelectorAll('input[type="checkbox"]'))
            .filter((cb) => cb.checked)
            .map((cb) => cb.value);
    }

    function toCsv(labels, series) {
        const header = ['year', ...series.map((s) => s.label)];

        const rows = labels.map((year, idx) => {
            const vals = series.map((s) => s.data[idx] ?? '');
            return [year, ...vals];
        });

        return [header, ...rows].map((row) => row.join(',')).join('\n');
    }

    async function fetchData(cols) {
        const params = new URLSearchParams();

        cols.forEach((col) => params.append('cols[]', col));

        const response = await fetch(`${jsonUrl}?${params.toString()}`, {
            headers: {
                Accept: 'application/json',
            },
        });

        return await response.json();
    }

    function clampRange() {
        let minV = parseInt(yearMin.value, 10);
        let maxV = parseInt(yearMax.value, 10);

        if (minV > maxV) {
            const tmp = minV;
            minV = maxV;
            maxV = tmp;

            yearMin.value = minV;
            yearMax.value = maxV;
        }

        yearMinLabel.textContent = String(minV);
        yearMaxLabel.textContent = String(maxV);

        return { minV, maxV };
    }

    function applyYearFilter(labels, seriesArr) {
        const { minV, maxV } = clampRange();

        const indexes = labels
            .map((year, index) => ({ year, index }))
            .filter((item) => item.year >= minV && item.year <= maxV)
            .map((item) => item.index);

        const outLabels = indexes.map((index) => labels[index]);

        const outSeries = seriesArr.map((series) => ({
            ...series,
            data: indexes.map((index) => series.data[index]),
        }));

        return { outLabels, outSeries };
    }

    function normalizeSeries(labels, seriesArr) {
        return seriesArr.map((series) => {
            let base = null;

            for (let i = 0; i < series.data.length; i += 1) {
                const value = series.data[i];

                if (value !== null && value !== undefined && value !== '') {
                    base = Number(value);

                    if (!Number.isNaN(base)) {
                        break;
                    }
                }
            }

            if (base === null || base === 0 || Number.isNaN(base)) {
                return series;
            }

            return {
                ...series,
                data: series.data.map((value) => {
                    if (value === null || value === undefined || value === '') {
                        return null;
                    }

                    const numberValue = Number(value);

                    if (Number.isNaN(numberValue)) {
                        return null;
                    }

                    return (numberValue / base) * 100;
                }),
            };
        });
    }

    function applyLogFilter(seriesArr) {
        return seriesArr.map((series) => ({
            ...series,
            data: series.data.map((value) => {
                if (value === null || value === undefined || value === '') {
                    return null;
                }

                const numberValue = Number(value);

                if (!Number.isFinite(numberValue) || numberValue <= 0) {
                    return null;
                }

                return numberValue;
            }),
        }));
    }

    function render(data) {
        currentData = data;

        const tension = parseFloat(smooth.value);
        smoothVal.textContent = tension.toFixed(2);

        let labels = [...data.labels];

        let seriesArr = data.series.map((series) => ({
            ...series,
            data: [...series.data],
        }));

        ({ outLabels: labels, outSeries: seriesArr } = applyYearFilter(labels, seriesArr));

        if (toggleNormalize.checked) {
            seriesArr = normalizeSeries(labels, seriesArr);
        }

        const useLog = toggleLog.checked;

        logWarning.classList.toggle('d-none', !useLog);

        if (useLog) {
            seriesArr = applyLogFilter(seriesArr);
        }

        updateEmissionAiSummary(labels, seriesArr);

        const datasets = seriesArr.map((series, index) => ({
            label: series.label,
            yAxisID: axisByKey[series.key] || defaultAxisForKey(series.key),
            data: labels.map((year, itemIndex) => ({
                x: year,
                y: series.data[itemIndex],
            })),
            parsing: false,
            borderColor: randColor(index),
            backgroundColor: 'transparent',
            tension,
            cubicInterpolationMode: 'monotone',
            spanGaps: true,
            pointRadius: 2,
            pointHoverRadius: 4,
        }));

        if (chart) {
            chart.destroy();
        }

        chart = new Chart(canvas, {
            type: 'line',
            data: {
                datasets,
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'nearest',
                    intersect: false,
                },
                scales: {
                    x: {
                        type: 'linear',
                        title: {
                            display: true,
                            text: 'Year',
                            color: chartMutedColor(),
                        },
                        ticks: {
                            precision: 0,
                            color: chartTextColor(),
                        },
                        grid: {
                            color: chartGridColor(),
                        },
                    },
                    y: {
                        type: useLog ? 'logarithmic' : 'linear',
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Left axis',
                            color: chartMutedColor(),
                        },
                        ticks: {
                            color: chartTextColor(),
                        },
                        grid: {
                            color: chartGridColor(),
                        },
                    },
                    y1: {
                        type: useLog ? 'logarithmic' : 'linear',
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Right axis',
                            color: chartMutedColor(),
                        },
                        ticks: {
                            color: chartTextColor(),
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    },
                },
                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            color: chartTextColor(),
                        },
                    },
                    tooltip: {
                        backgroundColor: isDarkTheme() ? '#111827' : '#ffffff',
                        titleColor: chartTextColor(),
                        bodyColor: chartTextColor(),
                        borderColor: chartGridColor(),
                        borderWidth: 1,
                    },
                    zoom: {
                        zoom: {
                            wheel: {
                                enabled: true,
                            },
                            pinch: {
                                enabled: true,
                            },
                            mode: 'x',
                        },
                        pan: {
                            enabled: true,
                            mode: 'x',
                        },
                    },
                },
            },
        });
    }

    async function updateEmissionAiSummaryWithEngine(labels, seriesArr) {
        const el = document.getElementById('emissionAiSummary');

        if (!el) {
            return;
        }

        const aiUrl = root.dataset.aiSummaryUrl;

        if (!aiUrl) {
            updateEmissionAiSummaryWithEngine(labels, seriesArr);
            return;
        }

        const selected = seriesArr.map((series) => {
            const clean = series.data
                .map((value, index) => ({
                    year: labels[index],
                    value: Number(value),
                }))
                .filter((point) => Number.isFinite(point.value));

            if (clean.length < 2) {
                return {
                    label: series.label,
                };
            }

            const values = clean.map((point) => point.value);
            const first = clean[0];
            const last = clean[clean.length - 1];
            const change = last.value - first.value;

            return {
                label: series.label,
                first_year: first.year,
                last_year: last.year,
                first_value: first.value,
                last_value: last.value,
                percent_change: first.value !== 0 ? (change / first.value) * 100 : null,
                min: Math.min(...values),
                max: Math.max(...values),
                avg: values.reduce((sum, value) => sum + value, 0) / values.length,
            };
        });

        try {
            el.textContent = 'Generating AI summary...';

            const response = await fetch(aiUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    selected,
                    normalize: toggleNormalize.checked,
                    log_scale: toggleLog.checked,
                }),
            });

            const json = await response.json();

            if (!response.ok || json.ok === false) {
                throw new Error(json.summary || 'AI summary unavailable.');
            }

            el.textContent = json.summary;
        } catch (error) {
            console.warn(error);
            updateEmissionAiSummary(labels, seriesArr);
        }
    }

    async function loadAndRender(cols) {
        const data = await fetchData(cols);

        if (availableCols.length === 0) {
            availableCols = data.available;

            const years = data.labels
                .map((year) => Number(year))
                .filter((year) => Number.isFinite(year));

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

            buildPicker(data.series.map((series) => series.key));
        }

        render(data);
    }

    document.getElementById('btnResetZoom').addEventListener('click', () => {
        chart?.resetZoom();
    });

    smooth.addEventListener('input', () => {
        const tension = parseFloat(smooth.value);

        smoothVal.textContent = tension.toFixed(2);

        if (!chart) {
            return;
        }

        chart.data.datasets.forEach((dataset) => {
            dataset.tension = tension;
            delete dataset.cubicInterpolationMode;
        });

        chart.update();
    });

    toggleNormalize.addEventListener('change', () => currentData && render(currentData));
    toggleLog.addEventListener('change', () => currentData && render(currentData));
    yearMin.addEventListener('input', () => currentData && render(currentData));
    yearMax.addEventListener('input', () => currentData && render(currentData));

    document.getElementById('btnPng').addEventListener('click', () => {
        if (!chart) {
            return;
        }

        const anchor = document.createElement('a');
        anchor.href = chart.toBase64Image('image/png', 1);
        anchor.download = 'emission_trends.png';
        anchor.click();
    });

    document.getElementById('btnCsv').addEventListener('click', () => {
        if (!currentData) {
            return;
        }

        let labels = [...currentData.labels];

        let seriesArr = currentData.series.map((series) => ({
            ...series,
            data: [...series.data],
        }));

        ({ outLabels: labels, outSeries: seriesArr } = applyYearFilter(labels, seriesArr));

        if (toggleNormalize.checked) {
            seriesArr = normalizeSeries(labels, seriesArr);
        }

        if (toggleLog.checked) {
            seriesArr = applyLogFilter(seriesArr);
        }

        const csv = toCsv(labels, seriesArr);
        const blob = new Blob([csv], {
            type: 'text/csv;charset=utf-8;',
        });

        const url = URL.createObjectURL(blob);
        const anchor = document.createElement('a');

        anchor.href = url;
        anchor.download = 'emission_trends.csv';
        anchor.click();

        URL.revokeObjectURL(url);
    });

    window.addEventListener('brc:theme-changed', () => {
        if (currentData) {
            render(currentData);
        }
    });

    loadAndRender([]);
}