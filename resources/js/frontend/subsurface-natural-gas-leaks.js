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
    let surveyRows = [];
    let temporalRows = [];
        const downloadCsv = (filename, rows) => {
        if (!rows.length) {
            showDashboardError('There are no measurements to download.');
            return;
        }

        const columns = Object.keys(rows[0]);

        const escapeCsv = (value) => {
            if (value === null || value === undefined) {
                return '';
            }

            const text = typeof value === 'object'
                ? JSON.stringify(value)
                : String(value);

            // Quote fields containing commas, quotes, or line breaks.
            return `"${text.replace(/"/g, '""')}"`;
        };

        const csv = [
            columns.map(escapeCsv).join(','),
            ...rows.map(row =>
                columns.map(column => escapeCsv(row[column])).join(',')
            ),
        ].join('\r\n');

        const blob = new Blob(
            ['\uFEFF', csv],
            { type: 'text/csv;charset=utf-8;' }
        );

        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');

        link.href = url;
        link.download = filename;

        document.body.appendChild(link);
        link.click();
        link.remove();

        URL.revokeObjectURL(url);
    };

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
                surveyRows = data.rows || [];
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

                // Additional information for chart tooltips.
                wellId: row.well_id,
                wellType: row.well_type,
                wellStatus: row.well_status,
                sampleDate: row.flux_sample_date,
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
                   const scaleType =
            document.getElementById('surveyScale').value;

        const isLogarithmic = scaleType === 'logarithmic';

        const chartPoints = isLogarithmic
            ? points.filter(point => point.y > 0)
            : points;

        const excludedCount = points.length - chartPoints.length;

        const chartStatus = document.getElementById('surveyChartStatus');

        if (chartStatus) {
            chartStatus.textContent = isLogarithmic
                ? `Displaying ${chartPoints.length} positive measurements. ` +
                  `${excludedCount} zero or negative measurements excluded ` +
                  'from the logarithmic view.'
                : `Displaying ${points.length} measurements.`;
        }

        surveyChart = new Chart(
            document.getElementById('surveyChart'),
            {
                type: 'scatter',
                data: {
                    datasets: [
                        {
                            label: `${metricLabel} versus distance`,
                            data: chartPoints,
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
                            type: isLogarithmic ? 'logarithmic' : 'linear',
                            title: {
                                display: true,
                                text: 'Flux (mg/m²/hr)',
                            },
                        },
                    },
                    interaction: {
                        mode: 'nearest',
                        intersect: true,
                    },
                    plugins: {
                        tooltip: {
                            mode: 'nearest',
                            intersect: true,
                            callbacks: {
                                label(context) {
                                    const point = context.raw;

                                    return [
                                        `Well ID: ${point.wellId ?? 'Not specified'}`,
                                        `Well type: ${point.wellType ?? 'Not specified'}`,
                                        `Well status: ${point.wellStatus ?? 'Not specified'}`,
                                        `Sampling date: ${point.sampleDate
    ? new Date(point.sampleDate).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        timeZone: 'UTC',
    })
    : 'Not specified'}`,
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
               const startDate = document.getElementById('temporalStart').value;
        const endDate = document.getElementById('temporalEnd').value;

        if (startDate && endDate && startDate > endDate) {
            throw new Error('Start date must be before or equal to end date.');
        }

        if (startDate) {
            params.set('start', `${startDate} 00:00:00`);
        }

        if (endDate) {
            params.set('end', `${endDate} 23:59:59`);
        }

        const data = await getJson(
            `${urls.temporal}?${params.toString()}`
        );
                temporalRows = data.rows || [];

        const points = (data.rows || [])
            .filter(row =>
                row.measured_at &&
                row[data.column] !== null &&
                row[data.column] !== undefined
            )
            .map(row => ({
            x: new Date(row.measured_at.replace(' ', 'T')).getTime(),
                y: Number(row[data.column]),
            }))
            .filter(point => Number.isFinite(point.x) && Number.isFinite(point.y));

                    if (temporalChart) {
            temporalChart.destroy();
            temporalChart = null;
        }

        const chartStatus = document.getElementById('temporalChartStatus');
        const chartCanvas = document.getElementById('temporalChart');

        if (points.length === 0) {
            chartStatus.textContent =
                'No measurements available for the selected site, ' +
                'chamber, and date range. Try changing the filters.';

            chartCanvas.classList.add('d-none');
            return;
        }

        chartStatus.textContent =
            `Displaying ${points.length.toLocaleString()} measurements.`;

        chartCanvas.classList.remove('d-none');
         // Maximum time gap allowed between connected measurements.
        const hour = 60 * 60 * 1000;
        const day = 24 * hour;

        const aggregation = document.getElementById(
            'temporalAggregation'
        ).value;

        const maximumGap = aggregation === 'daily'
            ? 7 * day
            : 24 * hour;
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
                            spanGaps: maximumGap,
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
plugins: {
    tooltip: {
        callbacks: {
            title(items) {
                if (!items.length) return '';

                return new Date(items[0].parsed.x)
                    .toLocaleString();
            },
        },
    },
},
                    scales: {
                    x: {
    type: 'linear',
    title: {
        display: true,
        text: 'Measurement date and time',
    },
    ticks: {
        maxTicksLimit: 8,
        callback(value) {
            return new Date(Number(value)).toLocaleDateString();
        },
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
       document.getElementById('surveyScale')
        ?.addEventListener('change', () => {
            hideDashboardError();

            loadSurvey(false).catch((error) => {
                console.error(error);

                showDashboardError(
                    'Unable to update the survey chart scale.'
                );
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
        document.getElementById('downloadSurveyCsv')
        ?.addEventListener('click', () => {
            hideDashboardError();

            const metric = document.getElementById('surveyMetric').value;
            const wellType = document.getElementById('surveyWellType').value;
            const wellStatus = document.getElementById('surveyWellStatus').value;

            const filename = [
                'subsurface_soil_survey',
                metric || 'ch4_flux',
                wellType || 'all_types',
                wellStatus || 'all_statuses',
            ].join('_') + '.csv';

            downloadCsv(filename, surveyRows);
        });
    
    document.getElementById('resetSurvey')
        ?.addEventListener('click', () => {
            hideDashboardError();

            // Restore the default survey filters.
            document.getElementById('surveyMetric').value = 'ch4_flux';
            document.getElementById('surveyWellType').value = '';
            document.getElementById('surveyWellStatus').value = '';
            document.getElementById('surveyScale').value = 'linear';

            // Reload the survey chart.
            loadSurvey(false).catch((error) => {
                console.error(error);

                showDashboardError(
                    'Unable to reset the survey chart.'
                );
            });
        });

        document.getElementById('downloadTemporalCsv')
        ?.addEventListener('click', () => {
            hideDashboardError();

            const site = document.getElementById('temporalSite').value;
            const gas = document.getElementById('temporalGas').value;
            const chamber = document.getElementById('temporalChamber').value;
            const aggregation =
                document.getElementById('temporalAggregation').value;

            const filename = [
                'subsurface_temporal',
                site,
                gas,
                `chamber_${chamber}`,
                aggregation,
            ].join('_') + '.csv';

            downloadCsv(filename, temporalRows);
        });

    document.getElementById('resetTemporal')
        ?.addEventListener('click', () => {
            hideDashboardError();

            const siteSelect = document.getElementById('temporalSite');

            // Restore the preferred monitoring site.
            const preferredSite = Array.from(siteSelect.options)
                .find(option => option.value === 'UPW1');

            if (preferredSite) {
                siteSelect.value = 'UPW1';
            } else if (siteSelect.options.length > 0) {
                siteSelect.selectedIndex = 0;
            }

            // Restore the default measurement settings.
            document.getElementById('temporalGas').value = 'ch4';
            document.getElementById('temporalChamber').value = '1';
            document.getElementById('temporalAggregation').value = 'hourly';

            // Remove the selected date restrictions.
            document.getElementById('temporalStart').value = '';
            document.getElementById('temporalEnd').value = '';

            // Reload the chart using the restored settings.
            loadTemporal().catch((error) => {
                console.error(error);

                showDashboardError(
                    'Unable to reset the temporal measurement chart.'
                );
            });
        });

    initializeDashboard();
});
