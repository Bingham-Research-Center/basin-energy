import Chart from 'chart.js/auto';
document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('oilWellPadDashboard');

    if (!root) {
        return;
    }

    const urls = {
        overview: root.dataset.overviewUrl,
        samples: root.dataset.samplesUrl,
        composition: root.dataset.compositionUrl,
        options: root.dataset.optionsUrl,
    };

    let sourceSummaryChart = null;
    let sampleChart = null;
    let compositionChart = null;

    const errorBox = document.getElementById('oilWellPadError');

    const showError = (message) => {
        errorBox.textContent = message;
        errorBox.classList.remove('d-none');
    };

    const clearError = () => {
        errorBox.textContent = '';
        errorBox.classList.add('d-none');
    };

    const getJson = async (url) => {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            const body = await response.text();
            console.error('Oil-well-pad request failed', response.status, url, body);
            throw new Error(`Request failed with status ${response.status}`);
        }

        return response.json();
    };

    const formatNumber = (value, digits = 1) => {
        const number = Number(value);
        return Number.isFinite(number)
            ? number.toLocaleString(undefined, { maximumFractionDigits: digits })
            : '—';
    };

    async function loadOptions() {
        const data = await getJson(urls.options);
        const sampleType = document.getElementById('oilSampleType');
        const metric = document.getElementById('oilMetric');

        (data.sample_types || []).forEach((value) => {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = value;
            sampleType.appendChild(option);
        });

        (data.metrics || []).forEach((item) => {
            const option = document.createElement('option');
            option.value = item.key;
            option.textContent = item.label;
            metric.appendChild(option);
        });

        metric.value = 'total_organic_compounds_g_hr';
    }

    async function loadOverview() {
        const data = await getJson(urls.overview);

        document.getElementById('oilMeasurementCount').textContent = formatNumber(data.measurement_count, 0);
        document.getElementById('oilSourceTypeCount').textContent = formatNumber(data.source_type_count, 0);
        document.getElementById('oilMaxOrganic').textContent = `${formatNumber(data.max_total_organic_g_hr)} g/hr`;
        document.getElementById('oilMaxMethane').textContent = `${formatNumber(data.max_methane_g_hr)} g/hr`;

        if (sourceSummaryChart) {
            sourceSummaryChart.destroy();
        }

        const summary = data.source_summary || [];

        sourceSummaryChart = new Chart(document.getElementById('oilSourceSummaryChart'), {
            type: 'bar',
            data: {
                labels: summary.map(row => row.sample_type),
                datasets: [
                    {
                        label: 'Average methane',
                        data: summary.map(row => row.avg_methane_g_hr),
                    },
                    {
                        label: 'Average non-methane hydrocarbons',
                        data: summary.map(row => row.avg_tnmhc_g_hr),
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { stacked: true },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        title: { display: true, text: 'Average emission rate (g/hr)' },
                    },
                },
            },
        });
    }

    async function loadSampleChart() {
        const sampleType = document.getElementById('oilSampleType').value;
        const metric = document.getElementById('oilMetric').value;
        const params = new URLSearchParams({ metric });

        if (sampleType) {
            params.set('sample_type', sampleType);
        }

        const data = await getJson(`${urls.samples}?${params.toString()}`);
        const rows = (data.rows || []).filter(row => row[metric] !== null);

        if (sampleChart) {
            sampleChart.destroy();
        }

        sampleChart = new Chart(document.getElementById('oilSampleChart'), {
            type: 'bar',
            data: {
                labels: rows.map(row => `Sample ${row.sample_number}`),
                datasets: [{
                    label: data.metric_label,
                    data: rows.map(row => Number(row[metric])),
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        ticks: { autoSkip: false, maxRotation: 70, minRotation: 45 },
                    },
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: data.metric_label },
                    },
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            afterLabel(context) {
                                const row = rows[context.dataIndex];
                                return [
                                    `Source: ${row.sample_type}`,
                                    row.notes ? `Component: ${row.notes}` : '',
                                ].filter(Boolean);
                            },
                        },
                    },
                },
            },
        });
    }

    async function loadCompositionChart() {
        const sampleType = document.getElementById('oilSampleType').value;
        const params = new URLSearchParams();

        if (sampleType) {
            params.set('sample_type', sampleType);
        }

        const url = params.toString()
            ? `${urls.composition}?${params.toString()}`
            : urls.composition;

        const data = await getJson(url);

        if (compositionChart) {
            compositionChart.destroy();
        }

        compositionChart = new Chart(document.getElementById('oilCompositionChart'), {
            type: 'bar',
            data: {
                labels: (data.groups || []).map(group => group.label),
                datasets: [{
                    label: 'Average emission rate (g/hr)',
                    data: (data.groups || []).map(group => group.value),
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        title: { display: true, text: 'Average emission rate (g/hr)' },
                    },
                },
            },
        });
    }

    async function updateInteractiveCharts() {
        clearError();

        try {
            await Promise.all([loadSampleChart(), loadCompositionChart()]);
        } catch (error) {
            console.error(error);
            showError('Unable to update the oil-well-pad emission charts. Check the browser console and Laravel log.');
        }
    }

    document.getElementById('oilUpdateChart').addEventListener('click', updateInteractiveCharts);
    document.getElementById('oilSampleType').addEventListener('change', updateInteractiveCharts);
    document.getElementById('oilMetric').addEventListener('change', loadSampleChart);

    (async () => {
        try {
            await loadOptions();
            await Promise.all([loadOverview(), loadSampleChart(), loadCompositionChart()]);
        } catch (error) {
            console.error(error);
            showError('Unable to load the oil-well-pad emissions dashboard. Check the browser console and Laravel log.');
        }
    })();
});
