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
    const temperature = document.getElementById('adminWeatherTemperature');
    const wind = document.getElementById('adminWeatherWind');
    const direction = document.getElementById('adminWeatherWindDirection');
    const humidity = document.getElementById('adminWeatherHumidity');
    const conditions = document.getElementById('adminWeatherConditions');

    if (!url || !button || !status || !temperature || !wind ||
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

    async function refresh() {
        button.disabled = true;
        status.textContent = 'Fetching the latest Vernal station report...';
        try {
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' }
            });
            const data = await response.json();

            if (!data || data.ok !== true || !response.ok) {
                present({});
                status.textContent = data && data.message ?
                    data.message : 'Weather observations are currently unavailable.';
                return;
            }

            present(data);
            const time = observedTime(data.observed_at);
            status.textContent = (data.stale ? 'Older observation · ' : 'Latest station report · ') +
                'KVEL' + (time ? ' · ' + time : '') +
                (data.message ? ' · ' + data.message : '');
        } catch (error) {
            console.warn('Admin weather:', error);
            present({});
            status.textContent = 'Unable to load NWS weather observations. Retry later.';
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
