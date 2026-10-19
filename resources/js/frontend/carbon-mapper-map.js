document.addEventListener('DOMContentLoaded', async function () {
    const mapElement = document.getElementById('map');

    if (!mapElement) {
        return;
    }

    if (typeof L === 'undefined') {
        console.error('Carbon Mapper: Leaflet has not loaded.');
        return;
    }

    // Initialize the map.
    const map = L.map('map').setView([40.15, -110.2], 8);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(map);

    // Safely display text in popup HTML.
    function escapePopupText(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (character) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[character];
        });
    }

    function formatDate(value, includeTime = true) {
        if (!value) {
            return 'Date unavailable';
        }

        const date = new Date(value);

        if (Number.isNaN(date.getTime())) {
            return 'Date unavailable';
        }

        return includeTime
            ? date.toLocaleString()
            : date.toLocaleDateString();
    }

    // Load the Uintah Basin coverage boundary.
    try {
        const boundaryResponse = await fetch(
            '/carbon-mapper/utah/boundary'
        );

        if (!boundaryResponse.ok) {
            throw new Error(
                `Boundary request failed: ${boundaryResponse.status}`
            );
        }

        const boundaryGeoJSON = await boundaryResponse.json();

        const boundaryLayer = L.geoJSON(boundaryGeoJSON, {
            style: {
                color: '#26734d',
                weight: 3,
                opacity: 0.9,
                fillColor: '#48a078',
                fillOpacity: 0.10
            }
        }).addTo(map);

        const boundaryBounds = boundaryLayer.getBounds();

        if (boundaryBounds.isValid()) {
            map.fitBounds(boundaryBounds, {
                padding: [25, 25],
                maxZoom: 10
            });
        }
    } catch (error) {
        console.error(
            'Carbon Mapper: Could not load the basin boundary.',
            error
        );
    }

    // Create the layer for individual methane observations.
    const detectionLayer = L.markerClusterGroup({
        maxClusterRadius: 30,
        disableClusteringAtZoom: 12
    });

    // The repeated-observation layer is separate from individual detections.
    const sourcesLayer = L.layerGroup().addTo(map);

    // Find the filter controls on the page.
    const fromInput = document.getElementById('carbonMapperFrom');
    const toInput = document.getElementById('carbonMapperTo');
    const typeInput = document.getElementById(
        'carbonMapperDetectionType'
    );
    const applyButton = document.getElementById(
        'carbonMapperApplyFilters'
    );
    const resetButton = document.getElementById(
        'carbonMapperResetFilters'
    );
    const statusElement = document.getElementById(
        'carbonMapperFilterStatus'
    );

    function setStatus(message) {
        if (statusElement) {
            statusElement.textContent = message;
        }
    }

    // Hide individual detections when zoomed too far out.
    function updateDetectionVisibility() {
        if (map.getZoom() < 8) {
            if (map.hasLayer(detectionLayer)) {
                map.removeLayer(detectionLayer);
            }
        } else if (!map.hasLayer(detectionLayer)) {
            map.addLayer(detectionLayer);
        }
    }

    map.on('zoomend', updateDetectionVisibility);

    // Build a popup for an individual observation.
    function createObservationPopup(point) {
        const observedDate = formatDate(point.observed_at);
        const publishedDate = formatDate(
            point.published_at,
            false
        );

        const gas = escapePopupText(point.gas || 'CH4');
        const detectionType = escapePopupText(
            point.detection_type || 'Not specified'
        );
        const sector = escapePopupText(
            point.sector || 'Not specified'
        );

        const superEmitterStatus = point.is_super_emitter
            ? 'Potential super-emitter candidate'
            : 'Not flagged as a potential super-emitter';

        let emissionInformation = '';
let sourceEmissionInformation = '';

if (
    point.source_emission_rate !== null &&
    point.source_emission_rate !== undefined
) {
    const sourceRate = Number(point.source_emission_rate);

    if (Number.isFinite(sourceRate)) {
        const uncertainty = Number(point.source_emission_uncertainty);

        const uncertaintyText = Number.isFinite(uncertainty)
            ? ` ± ${escapePopupText(uncertainty.toLocaleString())}`
            : '';

        sourceEmissionInformation =
            `<br><strong>Source emission rate:</strong> ` +
            `${escapePopupText(sourceRate.toLocaleString())}` +
            `${uncertaintyText} ` +
            `${escapePopupText(point.source_emission_unit || 'kg/hr')}`;
    }
}
        if (
            point.emission_rate !== null &&
            point.emission_rate !== undefined &&
            point.emission_unit
        ) {
            const rate = Number(point.emission_rate);

            if (Number.isFinite(rate)) {
                emissionInformation =
                    `<br><strong>Emission rate:</strong> ` +
                    `${escapePopupText(rate.toLocaleString())} ` +
                    `${escapePopupText(point.emission_unit)}`;
            }
        }

        return `
            <div class="carbon-mapper-popup">
                <strong>Methane Observation</strong>
                <hr style="margin: 6px 0;">
                <strong>Gas:</strong> ${gas}<br>
                <strong>Observed:</strong> ${escapePopupText(observedDate)}<br>
                <strong>Published:</strong> ${escapePopupText(publishedDate)}<br>
                <strong>Detection type:</strong> ${detectionType}<br>
                <strong>Sector:</strong> ${sector}<br>
                <strong>Classification:</strong>
                ${escapePopupText(superEmitterStatus)}
                ${emissionInformation}
                ${sourceEmissionInformation}
            </div>
        `;
    }
// Update the summary statistics for the displayed observations.
function updateStatistics(detections) {
    const totalElement = document.getElementById('carbonMapperTotal');
    const candidatesElement = document.getElementById('carbonMapperCandidates');
    const typesElement = document.getElementById('carbonMapperTypes');
    const dateRangeElement = document.getElementById('carbonMapperDateRange');

    if (
        !totalElement ||
        !candidatesElement ||
        !typesElement ||
        !dateRangeElement
    ) {
        return;
    }

    // Total observations currently displayed.
    totalElement.textContent = detections.length.toLocaleString();

    // Count observations flagged as potential candidates.
    const candidateCount = detections.filter(function (point) {
        return point.is_super_emitter === true;
    }).length;

    candidatesElement.textContent = candidateCount.toLocaleString();

    // Count distinct detection types.
    const detectionTypes = new Set(
        detections
            .map(function (point) {
                return point.detection_type;
            })
            .filter(function (type) {
                return typeof type === 'string' && type.trim() !== '';
            })
    );

    typesElement.textContent = detectionTypes.size.toLocaleString();

    // Find the earliest and latest observation dates.
    const dates = detections
        .map(function (point) {
            if (!point.observed_at) {
                return null;
            }

            const date = new Date(point.observed_at);

            return Number.isNaN(date.getTime()) ? null : date;
        })
        .filter(function (date) {
            return date !== null;
        })
        .sort(function (a, b) {
            return a.getTime() - b.getTime();
        });

    if (dates.length === 0) {
        dateRangeElement.textContent = 'No dates available';
    } else {
        const earliest = dates[0].toLocaleDateString();
        const latest = dates[dates.length - 1].toLocaleDateString();

        dateRangeElement.textContent =
            earliest === latest
                ? earliest
                : `${earliest} – ${latest}`;
    }
}
    // Draw the observations returned by the server.
   function renderDetections(detections) {
    updateStatistics(detections);

    detectionLayer.clearLayers();

        detections.forEach(function (point) {
            const latitude = Number(point.latitude);
            const longitude = Number(point.longitude);

            if (
                !Number.isFinite(latitude) ||
                !Number.isFinite(longitude)
            ) {
                return;
            }

            // Orange indicates a potential candidate.
            // Purple indicates other methane observations.
            const isCandidate = point.is_super_emitter === true;

            const markerColor = isCandidate
                ? '#e67e22'
                : '#6f42c1';

            const marker = L.circleMarker(
                [latitude, longitude],
                {
                    radius: isCandidate ? 6 : 4,
                    color: markerColor,
                    fillColor: markerColor,
                    fillOpacity: isCandidate ? 0.85 : 0.45,
                    weight: isCandidate ? 2 : 1
                }
            );

            marker.bindPopup(createObservationPopup(point));

            detectionLayer.addLayer(marker);
        });

        updateDetectionVisibility();

        console.info(
            `Carbon Mapper: Displaying ${detections.length} observations.`
        );
    }

    // Load observations using the selected filters.
    async function loadDetections(useFilters = true) {
        setStatus('Loading observations...');

        if (applyButton) {
            applyButton.disabled = true;
        }

        try {
            const url = new URL(
                '/carbon-mapper/utah/json',
                window.location.origin
            );

            if (useFilters) {
                if (fromInput && fromInput.value) {
                    url.searchParams.set('from', fromInput.value);
                }

                if (toInput && toInput.value) {
                    url.searchParams.set('to', toInput.value);
                }

                if (typeInput && typeInput.value) {
                    url.searchParams.set(
                        'detection_type',
                        typeInput.value
                    );
                }
            }

            const response = await fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(
                    `Observation request failed: ${response.status}`
                );
            }

            const detections = await response.json();

            if (!Array.isArray(detections)) {
                throw new Error('The server returned an unexpected response.');
            }

            renderDetections(detections);

            setStatus(
                `Showing ${detections.length} methane observations.`
            );

            // Hide unfiltered source summaries while filters are active.
            const filtersActive = Boolean(
                useFilters &&
                (
                    (fromInput && fromInput.value) ||
                    (toInput && toInput.value) ||
                    (typeInput && typeInput.value)
                )
            );

            if (filtersActive) {
                if (map.hasLayer(sourcesLayer)) {
                    map.removeLayer(sourcesLayer);
                }
            } else if (!map.hasLayer(sourcesLayer)) {
                sourcesLayer.addTo(map);
            }
        } catch (error) {
            console.error(
                'Carbon Mapper: Could not load methane observations.',
                error
            );

            setStatus(
                'Could not load observations. Please try again.'
            );
        } finally {
            if (applyButton) {
                applyButton.disabled = false;
            }
        }
    }

    // Load the initial observations.
    try {
        const initialResponse = await fetch(
            '/carbon-mapper/utah/json',
            {
                headers: {
                    'Accept': 'application/json'
                }
            }
        );

        if (!initialResponse.ok) {
            throw new Error(
                `Detection request failed: ${initialResponse.status}`
            );
        }

        const initialDetections = await initialResponse.json();

        if (!Array.isArray(initialDetections)) {
            throw new Error('The server returned an unexpected response.');
        }

        // Build the dropdown from detection types in the available data.
        if (typeInput) {
            const types = [
                ...new Set(
                    initialDetections
                        .map(point => point.detection_type)
                        .filter(type => typeof type === 'string' && type.trim())
                )
            ].sort();

            types.forEach(function (type) {
                const option = document.createElement('option');

                option.value = type;
                option.textContent = type.toUpperCase();

                typeInput.appendChild(option);
            });
        }

        renderDetections(initialDetections);

        setStatus(
            `Showing ${initialDetections.length} methane observations.`
        );
    } catch (error) {
        console.error(
            'Carbon Mapper: Could not load methane observations.',
            error
        );

        setStatus('Could not load observations.');
    }

    // Apply the selected date and detection-type filters.
    if (applyButton) {
        applyButton.addEventListener('click', function () {
            if (
                fromInput &&
                toInput &&
                fromInput.value &&
                toInput.value &&
                fromInput.value > toInput.value
            ) {
                setStatus(
                    'The start date must be before or equal to the end date.'
                );

                return;
            }

            loadDetections(true);
        });
    }

    // Clear all filters and restore all available observations.
    if (resetButton) {
        resetButton.addEventListener('click', function () {
            if (fromInput) {
                fromInput.value = '';
            }

            if (toInput) {
                toInput.value = '';
            }

            if (typeInput) {
                typeInput.value = '';
            }

            loadDetections(false);
        });
    }

    // Load locations that have repeated methane observations.
    try {
        const sourcesResponse = await fetch(
            '/carbon-mapper/utah/sources'
        );

        if (!sourcesResponse.ok) {
            throw new Error(
                `Source request failed: ${sourcesResponse.status}`
            );
        }

        const sources = await sourcesResponse.json();

        sources.forEach(function (source) {
            const latitude = Number(source.latitude);
            const longitude = Number(source.longitude);
            const count = Number(source.observations);

            if (
                !Number.isFinite(latitude) ||
                !Number.isFinite(longitude) ||
                !Number.isFinite(count)
            ) {
                return;
            }

            const marker = L.circleMarker(
                [latitude, longitude],
                {
                    radius: Math.min(14, 4 + Math.log(count)),
                    color: '#8b2fc9',
                    fillColor: '#8b2fc9',
                    fillOpacity: 0.8,
                    weight: 1
                }
            );

            marker.bindPopup(`
                <strong>Repeated Methane Observations</strong><br>
                Observations: ${count}<br>
                Last observed:
                ${escapePopupText(source.last_seen || 'Date unavailable')}
            `);

            sourcesLayer.addLayer(marker);
        });

        console.info(
            `Carbon Mapper: Loaded ${sources.length} repeated-observation locations.`
        );
    } catch (error) {
        console.error(
            'Carbon Mapper: Could not load source summaries.',
            error
        );
    }

    window.setTimeout(function () {
        map.invalidateSize();
    }, 200);
});
