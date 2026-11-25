import L from 'leaflet';
import 'leaflet.markercluster';

// Leaflet and MarkerCluster are installed in package-lock.json and now
// bundled locally instead of relying on unpkg on the Admin dashboard.
async function initialiseCarbonMapperMap() {
    const mapElement = document.getElementById('map');

    if (!mapElement || mapElement.dataset.brcMapInitialized === 'true') {
        return;
    }

    const status = document.getElementById('carbonMapperFilterStatus');

    // The view supplies the correct admin or User-scoped endpoints.
    // Avoid absolute /carbon-mapper URLs: those are User-only.
    const api = {
        observations: mapElement.dataset.jsonUrl,
        boundary: mapElement.dataset.boundaryUrl,
        sources: mapElement.dataset.sourcesUrl,
        plumeTemplate: mapElement.dataset.plumeUrlTemplate
    };

    if (!api.observations || !api.boundary || !api.sources || !api.plumeTemplate) {
        if (status) status.textContent = 'Carbon Mapper endpoints are not configured.';
        return;
    }

    if (typeof L.markerClusterGroup !== 'function') {
        if (status) status.textContent = 'Carbon Mapper map library is unavailable.';
        console.error('Carbon Mapper: local MarkerCluster dependency has not initialized.');
        return;
    }

    mapElement.dataset.brcMapInitialized = 'true';

    // Initialize the map.
    const map = L.map('map').setView([40.15, -110.2], 8);

// Searchable geographic locations for Carbon Mapper observations.
// These locations are maintained manually because Carbon Mapper
// observations do not include nearby city or town names.
const searchLocations = [
    {
        name: 'Roosevelt, Utah',
        searchTerms: ['roosevelt'],
        latitude: 40.2994,
        longitude: -109.9888,
        radiusKm: 15,
        zoom: 11
    },
    {
        name: 'Vernal, Utah',
        searchTerms: ['vernal'],
        latitude: 40.4555,
        longitude: -109.5287,
        radiusKm: 15,
        zoom: 11
    },
    {
        name: 'Duchesne, Utah',
        searchTerms: ['duchesne'],
        latitude: 40.1633,
        longitude: -110.4029,
        radiusKm: 15,
        zoom: 11
    },
    {
        name: 'Myton, Utah',
        searchTerms: ['myton'],
        latitude: 40.1944,
        longitude: -110.0618,
        radiusKm: 15,
        zoom: 11
    },
    {
        name: 'Ballard, Utah',
        searchTerms: ['ballard'],
        latitude: 40.2894,
        longitude: -109.9432,
        radiusKm: 15,
        zoom: 11
    },
    {
        name: 'Fort Duchesne, Utah',
        searchTerms: ['fort duchesne'],
        latitude: 40.2880,
        longitude: -109.8590,
        radiusKm: 15,
        zoom: 11
    },
    {
        name: 'Naples, Utah',
        searchTerms: ['naples'],
        latitude: 40.4269,
        longitude: -109.4993,
        radiusKm: 15,
        zoom: 11
    },
    {
        name: 'Jensen, Utah',
        searchTerms: ['jensen'],
        latitude: 40.3683,
        longitude: -109.3507,
        radiusKm: 15,
        zoom: 11
    },
    {
        name: 'Altamont, Utah',
        searchTerms: ['altamont'],
        latitude: 40.3591,
        longitude: -110.2874,
        radiusKm: 15,
        zoom: 11
    },
    {
        name: 'Tabiona, Utah',
        searchTerms: ['tabiona'],
        latitude: 40.3538,
        longitude: -110.7104,
        radiusKm: 15,
        zoom: 11
    },
    {
        name: 'Lapoint, Utah',
        searchTerms: ['lapoint'],
        latitude: 40.4027,
        longitude: -109.7932,
        radiusKm: 15,
        zoom: 11
    },
    {
        name: 'Maeser, Utah',
        searchTerms: ['maeser'],
        latitude: 40.4772,
        longitude: -109.5857,
        radiusKm: 15,
        zoom: 11
    },
    {
        name: 'Gusher, Utah',
        searchTerms: ['gusher'],
        latitude: 40.2850,
        longitude: -109.6150,
        radiusKm: 15,
        zoom: 11
    },
    {
        name: 'Randlett, Utah',
        searchTerms: ['randlett'],
        latitude: 40.2330,
        longitude: -109.8180,
        radiusKm: 15,
        zoom: 11
    },
    {
        name: 'Ouray, Utah',
        searchTerms: ['ouray'],
        latitude: 40.0900,
        longitude: -109.6800,
        radiusKm: 15,
        zoom: 11
    },
    {
        name: 'Price, Utah',
        searchTerms: ['price'],
        latitude: 39.5994,
        longitude: -110.8107,
        radiusKm: 15,
        zoom: 11
    },
    {
        name: 'Bonanza Power Plant, Utah',
        searchTerms: ['bonanza', 'bonanza power plant'],
        latitude: 40.0860,
        longitude: -109.2840,
        radiusKm: 15,
        zoom: 12
    },
    {
        name: 'Uintah and Ouray Indian Reservation',
        searchTerms: [
            'uintah and ouray',
            'uintah reservation',
            'ouray reservation',
            'ute reservation',
            'reservation'
        ],
        latitude: 40.1500,
        longitude: -109.6500,
        radiusKm: 50,
        zoom: 10
    }
];

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
            api.boundary
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

    // Display the actual plume outline for the selected detection.
const plumeLayer = L.geoJSON(null, {
    style: {
        color: '#6f42c1',
        weight: 2,
        opacity: 0.9,
        fillColor: '#6f42c1',
        fillOpacity: 0.35
    }
}).addTo(map);

let plumeRequestController = null;

function setPlumeStatus(message) {
    const status = document.querySelector(
        '.leaflet-popup .carbon-mapper-plume-status'
    );

    if (status) {
        status.textContent = message;
    }
}

async function showObservationPlume(point) {
    if (plumeRequestController) {
        plumeRequestController.abort();
    }

    plumeLayer.clearLayers();

    if (point.gas !== 'CH4' || !point.observation_id) {
        return;
    }

    const controller = new AbortController();
    plumeRequestController = controller;

    setPlumeStatus('Loading methane plume outline...');

    try {
        const response = await fetch(
            api.plumeTemplate.replace('__OBSERVATION_ID__', encodeURIComponent(point.observation_id)),
            {
                signal: controller.signal,
                headers: {
                    Accept: 'application/json'
                }
            }
        );

        if (!response.ok) {
    if (!controller.signal.aborted) {
        setPlumeStatus(
            response.status === 404
                ? 'Plume outline unavailable from Carbon Mapper.'
                : 'Unable to retrieve plume outline.'
        );
    }

    return;
}

        const geoJson = await response.json();

        if (
            controller.signal.aborted ||
            geoJson.type !== 'FeatureCollection' ||
            !Array.isArray(geoJson.features)
        ) {
            return;
        }

        plumeLayer.addData(geoJson);

setPlumeStatus(
    geoJson.features.length > 0
        ? 'Methane plume outline displayed.'
        : 'Plume outline unavailable from Carbon Mapper.'
);
        } catch (error) {
    if (
        error.name !== 'AbortError' &&
        !controller.signal.aborted &&
        plumeRequestController === controller
    ) {
        console.warn('Unable to load methane plume:', error);
        setPlumeStatus('Unable to retrieve plume outline.');
    }
}
}

function clearObservationPlume() {
    if (plumeRequestController) {
        plumeRequestController.abort();
        plumeRequestController = null;
    }

    plumeLayer.clearLayers();
}

    // Find the filter controls on the page.
    // Search for Carbon Mapper sites and observations.
    const siteSearchInput = document.getElementById(
        'carbonMapperSiteSearch'
    );

    const siteResultsSelect = document.getElementById(
        'carbonMapperSiteResults'
    );

    const gasInput = document.getElementById('carbonMapperGas');
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

    const downloadCsvButton = document.getElementById(
        'carbonMapperDownloadCsv'
    );

// Store observations from the most recent successful map update.
let displayedObservations = [];

// Store Leaflet markers by their Carbon Mapper observation ID.
const observationMarkers = new Map();

// Calculate the distance in kilometers between two geographic points.
// Uses the Haversine formula.
function calculateDistanceKm(lat1, lon1, lat2, lon2) {
    const earthRadiusKm = 6371;

    const toRadians = function (degrees) {
        return degrees * Math.PI / 180;
    };

    const latitudeDifference = toRadians(lat2 - lat1);
    const longitudeDifference = toRadians(lon2 - lon1);

    const a =
        Math.sin(latitudeDifference / 2) ** 2 +
        Math.cos(toRadians(lat1)) *
        Math.cos(toRadians(lat2)) *
        Math.sin(longitudeDifference / 2) ** 2;

    const c = 2 * Math.atan2(
        Math.sqrt(a),
        Math.sqrt(1 - a)
    );

    return earthRadiusKm * c;
}

// Find sites and observations in the currently loaded map data.
function updateSiteSearchResults() {
    if (!siteSearchInput || !siteResultsSelect) {
        return;
    }

    const searchTerm = siteSearchInput.value.trim().toLowerCase();

    siteResultsSelect.replaceChildren();
    siteResultsSelect.hidden = true;

    if (!searchTerm) {
        return;
    }

    // Find geographic locations matching the user's search.
    const matchingLocations = searchLocations.filter(function (location) {
        return location.searchTerms.some(function (term) {
            return term.includes(searchTerm);
        });
    });

    // Add matching locations and their nearby observations.
    matchingLocations.forEach(function (location) {
        const locationOption = document.createElement('option');

        locationOption.value =
            `location:${searchLocations.indexOf(location)}`;

        locationOption.textContent =
            `${location.name} — View area`;

        siteResultsSelect.appendChild(locationOption);

        // Find observations within the location's search radius.
        const nearbyObservations = displayedObservations
            .map(function (point) {
                const latitude = Number(point.latitude);
                const longitude = Number(point.longitude);

                if (
                    point.latitude == null ||
                    point.longitude == null ||
                    !Number.isFinite(latitude) ||
                    !Number.isFinite(longitude)
                ) {
                    return null;
                }

                const distanceKm = calculateDistanceKm(
                    location.latitude,
                    location.longitude,
                    latitude,
                    longitude
                );

                return {
                    point: point,
                    distanceKm: distanceKm
                };
            })
            .filter(function (result) {
                return result !== null &&
                    result.distanceKm <= location.radiusKm &&
                    observationMarkers.has(
                        String(result.point.observation_id)
                    );
            })
            .sort(function (a, b) {
                return a.distanceKm - b.distanceKm;
            })
            .slice(0, 20);

        // Add nearby observations below the location result.
        nearbyObservations.forEach(function (result) {
            const option = document.createElement('option');

            option.value = String(result.point.observation_id);

            const observationNumber = String(
                result.point.observation_number
            ).padStart(4, '0');

            option.textContent =
                `Observation #${observationNumber} — ${result.distanceKm.toFixed(1)} km away`;
            siteResultsSelect.appendChild(option);
        });
    });

    const matches = displayedObservations
        .filter(function (point) {
            const sourceName = String(point.source_name ?? '');
            const observationId = String(point.observation_id ?? '');

            const observationNumber = String(
                point.observation_number ?? ''
            );

            const formattedNumber = observationNumber.padStart(4, '0');

            // Accept numbers with or without "Observation" and "#".
            const normalizedSearch = searchTerm
                .replace(/^observation\s*#?\s*/i, '')
                .replace(/^#/, '')
                .trim();

            const numberMatches =
                observationNumber !== '' &&
                /^\d+$/.test(normalizedSearch) &&
                Number(normalizedSearch) === Number(observationNumber);

            return numberMatches ||
                sourceName.toLowerCase().includes(searchTerm) ||
                observationId.toLowerCase().includes(searchTerm) ||
                (
                    observationNumber !== '' &&
                    formattedNumber.includes(searchTerm)
                );
        })
        .slice(0, 50);

    matches.forEach(function (point) {
        const option = document.createElement('option');

        // Keep the original ID for selecting the correct map marker.
        option.value = String(point.observation_id ?? '');

        // Display a short, easy-to-remember observation number.
        const observationNumber = String(
            point.observation_number
        ).padStart(4, '0');

        option.textContent = `Observation #${observationNumber}`;

        siteResultsSelect.appendChild(option);
    });
    siteResultsSelect.hidden =
        matches.length === 0 && matchingLocations.length === 0;
}

// Update search results whenever the user types.
if (siteSearchInput) {
    siteSearchInput.addEventListener(
        'input',
        updateSiteSearchResults
    );
}

// Zoom to a selected location or Carbon Mapper observation.
if (siteResultsSelect) {
    siteResultsSelect.addEventListener('change', function () {
        const selectedValue = siteResultsSelect.value;

        // Handle geographic location selections.
        if (selectedValue.startsWith('location:')) {
            const locationIndex = Number(
                selectedValue.replace('location:', '')
            );

            const location = searchLocations[locationIndex];

            if (!location) {
                return;
            }

            // Center the map on the selected location.
            map.setView(
                [location.latitude, location.longitude],
                location.zoom
            );

            return;
        }

        // Handle existing observation selections.
        const observationId = selectedValue;
        const marker = observationMarkers.get(observationId);

        if (!marker) {
            return;
        }

        // Individual observations are hidden below zoom level 8.
        if (map.getZoom() < 8) {
            map.setZoom(8);
        }

        if (!map.hasLayer(detectionLayer)) {
            map.addLayer(detectionLayer);
        }

        // Reveal the marker even when it belongs to a cluster.
        detectionLayer.zoomToShowLayer(marker, function () {
            map.panTo(marker.getLatLng());
            marker.openPopup();
        });
    });
}

function updateExportData(detections) {
    displayedObservations = detections;
    updateSiteSearchResults();

    if (downloadCsvButton) {
        downloadCsvButton.disabled = detections.length === 0;
    }
}
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

if (!emissionInformation && !sourceEmissionInformation) {
    emissionInformation = '<br><strong>Emission rate:</strong> Not available';
}
        // Display official Carbon Mapper source statistics.
        const formatSourceCount = (value) => {
            if (value === null || value === undefined || value === '') {
                return 'Not available';
            }

            const number = Number(value);

            return Number.isFinite(number)
                ? escapePopupText(number.toLocaleString())
                : 'Not available';
        };

        let sourceStatistics = '';

        if (
            point.source_persistence !== null &&
            point.source_persistence !== undefined
        ) {
            const persistence = Number(point.source_persistence);

            if (Number.isFinite(persistence)) {
                sourceStatistics +=
                    `<br><strong>Source persistence:</strong> ` +
                    `${escapePopupText((persistence * 100).toFixed(2))}%`;
            }
        }

        if (point.source_plume_count !== null &&
            point.source_plume_count !== undefined) {
            sourceStatistics +=
                `<br><strong>Number of plumes:</strong> ` +
                formatSourceCount(point.source_plume_count);
        }

        if (point.source_detection_days !== null &&
            point.source_detection_days !== undefined) {
            sourceStatistics +=
                `<br><strong>Days with detections:</strong> ` +
                formatSourceCount(point.source_detection_days);
        }

        if (point.source_observation_days !== null &&
            point.source_observation_days !== undefined) {
            sourceStatistics +=
                `<br><strong>Days observed:</strong> ` +
                formatSourceCount(point.source_observation_days);
        }
        if  (
            point.source_observation_count !== null &&
            point.source_observation_count !== undefined
        ) {
            sourceStatistics +=
                `<br><strong>Number of observations (scenes):</strong> ` +
                formatSourceCount(point.source_observation_count);
        }
        return `
                <div class="carbon-mapper-popup">
                <strong role="heading" aria-level="3">Carbon Mapper Observation</strong>
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
                ${sourceStatistics}
                ${point.gas === 'CH4' && point.observation_id
    ? '<div class="carbon-mapper-plume-status small mt-2" role="status" aria-live="polite"></div>'
    : ''}
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

    clearObservationPlume();
    detectionLayer.clearLayers();
    observationMarkers.clear();


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

// Position the popup differently depending on the zoom level.
marker.bindPopup(createObservationPopup(point), {
    offset: L.point(0, 0),
    autoPan: true
});

marker.on('popupopen', function () {
    const popup = marker.getPopup();

    if (map.getZoom() >= 12) {
        // Move the popup higher above the plume when zoomed in.
        popup.options.offset = L.point(0, -180);
    } else {
        // Normal popup position when zoomed out.
        popup.options.offset = L.point(0, 0);
    }

    popup.update();
});
marker.on('popupopen', function () {
    showObservationPlume(point);
});

marker.on('popupclose', function () {
    clearObservationPlume();
});

if (point.observation_id) {
    observationMarkers.set(String(point.observation_id), marker);
}

detectionLayer.addLayer(marker);

        });

        updateDetectionVisibility();

        // Refresh search results after rebuilding all map markers.
        updateExportData(detections);

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
                api.observations,
                window.location.origin
            );

            if (useFilters) {
    if (gasInput && gasInput.value) {
        url.searchParams.set('gas', gasInput.value);
    }

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

            const selectedGas = useFilters && gasInput ? gasInput.value : '';

const gasLabel = selectedGas === 'CH4'
    ? 'methane (CH4)'
    : selectedGas === 'CO2'
        ? 'carbon dioxide (CO2)'
        : 'Carbon Mapper';

if (detections.length === 0) {
    const noResultsMessage = selectedGas === 'CO2'
        ? 'No CO2 observations found for the selected filters.'
        : selectedGas === 'CH4'
            ? 'No CH4 observations found for the selected filters.'
            : 'No observations found for the selected filters.';

    setStatus(noResultsMessage);
} else {
    setStatus(
        `Showing ${detections.length} ${gasLabel} observations.`
    );
}
            // Hide unfiltered source summaries while filters are active.
            const filtersActive = Boolean(
    useFilters &&
    (
        (gasInput && gasInput.value) ||
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
    'Unable to retrieve Carbon Mapper observations. Please try again.'
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
            api.observations,
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

        if (initialDetections.length === 0) {
    setStatus(
        'No Carbon Mapper observations are currently available for this area.'
    );
} else {
    setStatus(
        `Showing ${initialDetections.length} Carbon Mapper observations.`
    );
}

    } catch (error) {
        console.error(
            'Carbon Mapper: Could not load methane observations.',
            error
        );

        setStatus(
    'Unable to retrieve Carbon Mapper observations. Please try again.'
);
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
            if (gasInput) {
    gasInput.value = '';
}
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
    // Download the currently displayed Carbon Mapper observations as CSV.
    if (downloadCsvButton) {
        downloadCsvButton.addEventListener('click', function () {
            if (displayedObservations.length === 0) {
                return;
            }

            const columns = [
                ['Observation ID', 'observation_id'],
                ['Gas', 'gas'],
                ['Observed At', 'observed_at'],
                ['Published At', 'published_at'],
                ['Latitude', 'latitude'],
                ['Longitude', 'longitude'],
                ['Detection Type', 'detection_type'],
                ['Sector', 'sector'],
                ['Potential Super Emitter', 'is_super_emitter'],
                ['Observation Emission Rate', 'emission_rate'],
                ['Observation Emission Unit', 'emission_unit'],
                ['Source Name', 'source_name'],
                ['Source Emission Rate', 'source_emission_rate'],
                ['Source Emission Unit', 'source_emission_unit'],
                ['Source Emission Uncertainty', 'source_emission_uncertainty']
            ];

            function escapeCsvValue(value) {
                if (value === null || value === undefined) {
                    return '';
                }

                let text = String(value);

                // Prevent spreadsheet programs from interpreting
                // untrusted values as formulas.
                if (/^[\s\u0000-\u001f]*[=+\-@]/.test(text)) {
                    text = "'" + text;
                }

                return '"' + text.replace(/"/g, '""') + '"';
            }

            const header = columns.map(column =>
                escapeCsvValue(column[0])
            ).join(',');

            const rows = displayedObservations.map(function (point) {
                return columns.map(function (column) {
                    return escapeCsvValue(point[column[1]]);
                }).join(',');
            });

            const csv = [header, ...rows].join('\r\n');

            const blob = new Blob(
                ['\uFEFF', csv],
                { type: 'text/csv;charset=utf-8;' }
            );

            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');

            link.href = url;
            link.download =
                'carbon-mapper-observations-' +
                new Date().toISOString().slice(0, 10) +
                '.csv';

            document.body.appendChild(link);
            link.click();
            link.remove();

            window.setTimeout(function () {
                URL.revokeObjectURL(url);
            }, 1000);
        });
    }

    // Load locations that have repeated methane observations.
    try {
        const sourcesResponse = await fetch(
            api.sources
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
}

// Robust to this bundle loading after DOMContentLoaded, including
// Livewire navigation, while preventing a second map on the same element.
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialiseCarbonMapperMap, { once: true });
} else {
    initialiseCarbonMapperMap();
}
