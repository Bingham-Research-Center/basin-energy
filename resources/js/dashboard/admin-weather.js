/**
 * Admin-only weather cards.
 *
 * The application server proxies/caches the public NWS observation.
 * No third-party request, email address or API key is exposed to the browser.
 */
function initialiseAdminWeather() {
    const root = document.getElementById('adminWeatherSummary');
    if (!root || root.dataset.initialised === 'true') return;

    const url = root.dataset.weatherUrl;
    const button = document.getElementById('adminWeatherRefresh');
    const status = document.getElementById('adminWeatherStatus');
    const observedAt = document.getElementById('adminWeatherObservedAt');
    const temperature = document.getElementById('adminWeatherTemperature');
    const wind = document.getElementById('adminWeatherWind');
    const direction = document.getElementById('adminWeatherWindDirection');
    const humidity = document.getElementById('adminWeatherHumidity');
    const conditions = document.getElementById('adminWeatherConditions');

    if (!url || !button || !status || !observedAt || !temperature || !wind ||
        !direction || !humidity || !conditions) {
        return;
    }

    root.dataset.initialised = 'true';

    const available = value => typeof value === 'number' && Number.isFinite(value);

    function present(data) {
        temperature.textContent = available(data.temperature_f) ?
            Math.round(data.temperature_f) + ' °F' : '—';
        wind.textContent = available(data.wind_mph) ?
            Number(data.wind_mph).toLocaleString('en-US', { maximumFractionDigits: 1 }) + ' mph' : '—';
        direction.textContent = data.wind_direction ?
            'From ' + data.wind_direction : 'Direction not reported';
        humidity.textContent = available(data.humidity_percent) ?
            Math.round(data.humidity_percent) + '%' : '—';
        conditions.textContent = typeof data.conditions === 'string' && data.conditions.trim() ?
            data.conditions : 'Not reported';
    }

    function observedTime(timestamp) {
        if (!timestamp) return '';
        const date = new Date(timestamp);
        return Number.isFinite(date.getTime()) ? date.toLocaleString('en-US', {
            timeZone: 'America/Denver',
            month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit',
            timeZoneName: 'short'
        }) : '';
    }

    // Keep the normal successful state uncluttered; only warnings are
    // announced below the cards. Timestamp and source stay in the header.
    function warn(message) {
        status.textContent = message || '';
        status.classList.toggle('d-none', !message);
    }

    async function refresh() {
        button.disabled = true;
        observedAt.textContent = 'Updating observation...';
        warn('');
        try {
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' }
            });
            const data = await response.json();

            if (!data || data.ok !== true || !response.ok) {
                present({});
                observedAt.textContent = 'Observation unavailable';
                warn(data && data.message ?
                    data.message : 'Weather observations are currently unavailable.');
                return;
            }

            present(data);
            const time = observedTime(data.observed_at);
            observedAt.textContent = time ? 'As of ' + time : 'Observation time unavailable';
            warn(data.stale ? (data.message || 'This observation may be out of date.') : '');
        } catch (error) {
            console.warn('Admin weather:', error);
            present({});
            observedAt.textContent = 'Observation unavailable';
            warn('Unable to load NWS weather observations. Retry later.');
        } finally {
            button.disabled = false;
        }
    }

    button.addEventListener('click', refresh);
    refresh();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialiseAdminWeather, { once: true });
} else {
    initialiseAdminWeather();
}
