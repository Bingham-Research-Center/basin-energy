async function loadHorsepool() {
    if (status) {
        status.textContent = 'Loading...';
    }

    if (refreshBtn) {
        refreshBtn.disabled = true;
    }

    try {
        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
            },
        });

        const json = await response.json();

        if (!response.ok || json.ok === false) {
            throw new Error(json.message || `Request failed with status ${response.status}`);
        }

        render(json.record || {}, json.meta || {});

        if (status) {
            status.textContent = `Fetched at ${new Date(json.meta?.fetched_at || Date.now()).toLocaleString()}`;
        }
    } catch (error) {
        console.error(error);

        content.className = 'dashboard-loading-panel dashboard-loading-panel-error';
        content.textContent = error.message || 'Unable to load station packet.';

        if (status) {
            status.textContent = 'Load failed.';
        }
    } finally {
        if (refreshBtn) {
            refreshBtn.disabled = false;
        }
    }
}