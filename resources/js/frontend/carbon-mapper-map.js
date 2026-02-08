document.addEventListener('DOMContentLoaded', async function () {
    const map = L.map('map').setView([39.3, -111.7], 6);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    try {
        const response = await fetch('/carbon-mapper/utah/json');
        const plumes = await response.json();

        plumes.forEach(p => {
            L.circleMarker([p.latitude, p.longitude], {
                radius: 6,
                color: '#6f42c1',
                fillOpacity: 0.8
            })
            .bindPopup(`
                <strong>Methane (CH₄)</strong><br>
                Sector: ${p.sector ?? 'N/A'}<br>
                Observed: ${p.observed_at ?? 'N/A'}
            `)
            .addTo(map);
        });

    } catch (e) {
        console.error('Carbon Mapper load failed', e);
    }
});
