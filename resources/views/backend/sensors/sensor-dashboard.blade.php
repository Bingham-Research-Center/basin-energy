@extends('backend.layouts.app')

@section('title', 'Sensor Dashboard')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Sensor Dashboard</strong>

            <div style="min-width: 260px;">
                <select id="deviceSelector" class="form-control">
                    <option value="">Loading devices...</option>
                </select>
            </div>
        </div>

        <div class="card-body" style="height: 400px;">
            <canvas id="sensorChart"></canvas>
        </div>
    </div>
</div>
@endsection

@push('after-scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const ctx = document.getElementById('sensorChart').getContext('2d');
    const deviceSelector = document.getElementById('deviceSelector');

    let currentDeviceId = null;
    let currentChannelName = null;

    const sensorChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'Temperature',
                    data: [],
                    borderWidth: 2,
                    tension: 0.3
                },
                {
                    label: 'Humidity',
                    data: [],
                    borderWidth: 2,
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    function resetChart() {
        sensorChart.data.labels = [];
        sensorChart.data.datasets[0].data = [];
        sensorChart.data.datasets[1].data = [];
        sensorChart.update();
    }

    function populateChart(rows) {
        const labels = [];
        const temperatureData = [];
        const humidityData = [];

        rows.forEach(item => {
            labels.push(item.recorded_at);
            temperatureData.push(item.temperature);
            humidityData.push(item.humidity);
        });

        sensorChart.data.labels = labels;
        sensorChart.data.datasets[0].data = temperatureData;
        sensorChart.data.datasets[1].data = humidityData;
        sensorChart.update();
    }

    function appendReading(reading) {
        sensorChart.data.labels.push(reading.recorded_at);
        sensorChart.data.datasets[0].data.push(reading.temperature);
        sensorChart.data.datasets[1].data.push(reading.humidity);

        const maxPoints = 30;

        if (sensorChart.data.labels.length > maxPoints) {
            sensorChart.data.labels.shift();
            sensorChart.data.datasets[0].data.shift();
            sensorChart.data.datasets[1].data.shift();
        }

        sensorChart.update();
    }

    function loadDevices() {
        fetch('/api/sensor-data/devices')
            .then(response => response.json())
            .then(result => {
                const devices = result.data || [];

                deviceSelector.innerHTML = '';

                if (!devices.length) {
                    deviceSelector.innerHTML = '<option value="">No devices found</option>';
                    resetChart();
                    return;
                }

                devices.forEach(deviceId => {
                    const option = document.createElement('option');
                    option.value = deviceId;
                    option.textContent = deviceId;
                    deviceSelector.appendChild(option);
                });

                currentDeviceId = devices[0];
                deviceSelector.value = currentDeviceId;

                loadDeviceData(currentDeviceId);
                subscribeToDevice(currentDeviceId);
            })
            .catch(error => {
                console.error('Error loading devices:', error);
                deviceSelector.innerHTML = '<option value="">Failed to load devices</option>';
            });
    }

    function loadDeviceData(deviceId) {
        fetch('/api/sensor-data/latest/' + encodeURIComponent(deviceId))
            .then(response => response.json())
            .then(result => {
                populateChart(result.data || []);
            })
            .catch(error => {
                console.error('Error loading device data:', error);
            });
    }

    function subscribeToDevice(deviceId) {
        if (!window.Echo) {
            console.error('Echo is not available.');
            return;
        }

        if (currentChannelName) {
            window.Echo.leave(currentChannelName);
        }

        currentChannelName = 'sensor-readings.' + deviceId;

        console.log('Subscribing to', currentChannelName);

        window.Echo.channel(currentChannelName)
            .listen('.sensor.reading.created', function (e) {
                console.log('Realtime event received:', e);
                appendReading(e);
            });
    }

    deviceSelector.addEventListener('change', function () {
        currentDeviceId = this.value;

        if (!currentDeviceId) {
            resetChart();
            return;
        }

        loadDeviceData(currentDeviceId);
        subscribeToDevice(currentDeviceId);
    });

    window.addEventListener('load', function () {
        loadDevices();
    });
</script>
@endpush