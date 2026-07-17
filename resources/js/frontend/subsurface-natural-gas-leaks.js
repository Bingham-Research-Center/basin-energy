document.addEventListener('DOMContentLoaded', () => {
    const page = document.getElementById('subsurfaceLeakDashboard');

    // Do nothing when this bundle is loaded on other pages.
    if (!page) {
        return;
    }

    const urls = window.subsurfaceLeakUrls;

    if (!urls) {
        console.error('Subsurface leak endpoint URLs are not defined.');
        return;
    }

    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not available.');
        return;
    }

    let surveyChart = null;
    let temporalChart = null;

    const getJson = async (url) => {
        const response = await fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const contentType = response.headers.get('content-type') || '';

        if (!response.ok) {
            const body = await response.text();

            console.error('Dashboard request failed:', {
                url,
                status: response.status,
                body,
            });

            throw new Error(
                `Request failed with status ${response.status}: ${url}`
            );
        }

        if (!contentType.includes('application/json')) {
            const body = await response.text();

            console.error('Expected JSON but received another response:', {
                url,
                contentType,
                body,
            });

            throw new Error(`Expected a JSON response from ${url}`);
        }

        return response.json();
    };

    const clearGeneratedOptions = (select) => {
        select.querySelectorAll('option[data-generated="true"]')
            .forEach(option => option.remove());
    };

    const addOptions = (
        select,
        items,
        valueKey = null,
        labelKey = null
    ) => {
        clearGeneratedOptions(select);

        items.forEach((item) => {
            const option = document.createElement('option');

            option.dataset.generated = 'true';
            option.value = valueKey ? item[valueKey] : item;
            option.textContent = labelKey ? item[labelKey] : item;

            select.appendChild(option);
        });
    };

    const showDashboardError = (message) => {
        const errorBox = document.getElementById('subsurfaceDashboardError');

        if (!errorBox) {
            return;
        }

        errorBox.textContent = message;
        errorBox.classList.remove('d-none');
    };

    const hideDashboardError = () => {
        const errorBox = document.getElementById('subsurfaceDashboardError');

        if (!errorBox) {
            return;
        }

        errorBox.classList.add('d-none');
        errorBox.textContent = '';
    };

    async function loadOverview() {
        const data = await getJson(urls.overview);
        const values = document.querySelectorAll('.overview-value');

        if (values.length < 4) {
            return;
        }

        values[0].textContent =
            Number(data.survey_records || 0).toLocaleString();

        values[1].textContent =
            Number(data.interval_records || 0).toLocaleString();

        values[2].textContent =
            Number(data.chamber_records || 0).toLocaleString();

        values[3].textContent =
            data.survey_ch4?.max == null
                ? '—'
                : `${Number(data.survey_ch4.max).toLocaleString()} mg/m²/hr`;
    }

    async function loadOptions() {
        const data = await getJson(urls.options);

        const surveyMetric = document.getElementById('surveyMetric');
        const temporalSite = document.getElementById('temporalSite');

        addOptions(
            surveyMetric,
            data.metrics || [],
            'key',
            'label'
        );

        addOptions(
            temporalSite,
            data.sites || [],
            'site_code',
            'site_code'
        );

        // Prefer a Utah production well as the initial site.
        const preferredSite = Array.from(temporalSite.options)
            .find(option => option.value === 'UPW1');

        if (preferredSite) {
            temporalSite.value = 'UPW1';
        }
    }

    async function loadSurvey(populateFilters = false) {
        const metricElement = document.getElementById('surveyMetric');
        const wellTypeElement = document.getElementById('surveyWellType');
        const wellStatusElement = document.getElementById('surveyWellStatus');

        const metric = metricElement.value || 'ch4_flux';
        const wellType = wellTypeElement.value;
        const wellStatus = wellStatusElement.value;

        const params = new URLSearchParams({
            metric,
        });

        if (wellType) {
            params.set('well_type', wellType);
        }

        if (wellStatus) {
            params.set('well_status', wellStatus);
        }

        const data = await getJson(`${urls.survey}?${params.toString()}`);

        if (populateFilters) {
            addOptions(
                wellTypeElement,
                data.options?.well_types || []
            );

            addOptions(
                wellStatusElement,
                data.options?.well_statuses || []
            );
        }

        const points = (data.rows || [])
            .filter(row =>
                row.flux_distance_m !== null &&
                row.flux_distance_m !== undefined &&
                row[metric] !== null &&
                row[metric] !== undefined
            )
            .map(row => ({
                x: Number(row.flux_distance_m),
                y: Number(row[metric]),
            }))
            .filter(point =>
                Number.isFinite(point.x) &&
                Number.isFinite(point.y)
            );

        if (surveyChart) {
            surveyChart.destroy();
        }

        const metricOption =
            metricElement.options[metricElement.selectedIndex];

        const metricLabel =
            metricOption?.textContent || metric.replaceAll('_', ' ');

        surveyChart = new Chart(
            document.getElementById('surveyChart'),
            {
                type: 'scatter',
                data: {
                    datasets: [
                        {
                            label: `${metricLabel} versus distance`,
                            data: points,
                            pointRadius: 3,
                            pointHoverRadius: 5,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    parsing: false,
                    scales: {
                        x: {
                            type: 'linear',
                            title: {
                                display: true,
                                text: 'Distance from wellhead (m)',
                            },
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Flux (mg/m²/hr)',
                            },
                        },
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label(context) {
                                    return [
                                        `Distance: ${context.parsed.x.toLocaleString()} m`,
                                        `Flux: ${context.parsed.y.toLocaleString()} mg/m²/hr`,
                                    ];
                                },
                            },
                        },
                    },
                },
            }
        );
    }

    async function loadTemporal() {
        const site = document.getElementById('temporalSite').value;

        if (!site) {
            throw new Error('No temporal measurement site is available.');
        }

        const params = new URLSearchParams({
            site,
            gas: document.getElementById('temporalGas').value,
            chamber: document.getElementById('temporalChamber').value,
            aggregation:
                document.getElementById('temporalAggregation').value,
        });

        const data = await getJson(
            `${urls.temporal}?${params.toString()}`
        );

        const points = (data.rows || [])
            .filter(row =>
                row.measured_at &&
                row[data.column] !== null &&
                row[data.column] !== undefined
            )
            .map(row => ({
                x: row.measured_at,
                y: Number(row[data.column]),
            }))
            .filter(point => Number.isFinite(point.y));

        if (temporalChart) {
            temporalChart.destroy();
        }

        temporalChart = new Chart(
            document.getElementById('temporalChart'),
            {
                type: 'line',
                data: {
                    datasets: [
                        {
                            label:
                                `${data.site} · Chamber ${data.chamber} · ` +
                                `${data.gas.toUpperCase()}`,
                            data: points,
                            pointRadius: 0,
                            pointHoverRadius: 4,
                            borderWidth: 1.5,
                            spanGaps: false,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    parsing: false,
                    interaction: {
                        mode: 'nearest',
                        intersect: false,
                    },
                    scales: {
                        x: {
                            type: 'category',
                            title: {
                                display: true,
                                text: 'Measurement time',
                            },
                            ticks: {
                                maxTicksLimit: 12,
                            },
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Flux (mg/m²/hr)',
                            },
                        },
                    },
                },
            }
        );
    }

    async function initializeDashboard() {
        hideDashboardError();

        try {
            await Promise.all([
                loadOverview(),
                loadOptions(),
            ]);

            await loadSurvey(true);
            await loadTemporal();
        } catch (error) {
            console.error('Subsurface dashboard initialization failed:', error);

            showDashboardError(
                'Unable to load one or more dashboard datasets. ' +
                'Please check the browser console and Laravel log.'
            );
        }
    }

    document.getElementById('surveyMetric')
        ?.addEventListener('change', () => {
            loadSurvey(false).catch((error) => {
                console.error(error);
                showDashboardError('Unable to update the survey chart.');
            });
        });

    document.getElementById('surveyWellType')
        ?.addEventListener('change', () => {
            loadSurvey(false).catch((error) => {
                console.error(error);
                showDashboardError('Unable to update the survey chart.');
            });
        });

    document.getElementById('surveyWellStatus')
        ?.addEventListener('change', () => {
            loadSurvey(false).catch((error) => {
                console.error(error);
                showDashboardError('Unable to update the survey chart.');
            });
        });

    document.getElementById('reloadTemporal')
        ?.addEventListener('click', () => {
            hideDashboardError();

            loadTemporal().catch((error) => {
                console.error(error);

                showDashboardError(
                    'Unable to update the temporal measurement chart.'
                );
            });
        });

    initializeDashboard();
});