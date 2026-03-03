document.addEventListener('DOMContentLoaded', async function () {
    // 1️⃣ Initialize map (Utah-centered)
    const map = L.map('map').setView([39.3, -111.7], 6);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // 2️⃣ Detection cluster layer (RAW detections)
    const detectionLayer = L.markerClusterGroup({
        maxClusterRadius: 30,
        disableClusteringAtZoom: 12
    });

    // 3️⃣ Load RAW detections (dense)
    try {
        const detectionsRes = await fetch('/carbon-mapper/utah/json');
        const detections = await detectionsRes.json();

        detections.forEach(p => {
            detectionLayer.addLayer(
                L.circleMarker([p.latitude, p.longitude], {
                    radius: 3,
                    color: '#6f42c1',
                    fillOpacity: 0.35,
                    weight: 0
                })
            );
        });

        map.addLayer(detectionLayer);
    } catch (e) {
        console.error('Detection load failed', e);
    }

    // 4️⃣ Load AGGREGATED sources (Carbon Mapper–style)
    try {
        const sourcesRes = await fetch('/carbon-mapper/utah/sources');
        const sources = await sourcesRes.json();

        sources.forEach(src => {
            L.circleMarker([src.latitude, src.longitude], {
                radius: Math.min(14, 4 + Math.log(src.observations)),
                color: '#6f42c1',
                fillOpacity: 0.85,
                weight: 1
            })
            .bindPopup(`
                <strong>CH₄ Source</strong><br>
                Observations: ${src.observations}<br>
                Last seen: ${src.last_seen}
            `)
            .addTo(map);
        });
    } catch (e) {
        console.error('Source load failed', e);
    }

    // 5️⃣ Zoom-based visibility control (KEY PART)
    map.on('zoomend', () => {
        const z = map.getZoom();

        if (z < 8) {
            map.removeLayer(detectionLayer);
        } else {
            map.addLayer(detectionLayer);
        }
    });
});
