@extends('backend.layouts.app')

@section('title', 'Sensor Dashboard')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <strong>Sensor Dashboard</strong>
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

    function loadSensorData() {
        fetch('/api/sensor-data/latest')
            .then(response => response.json())
            .then(result => {
                const rows = result.data || [];

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
            })
            .catch(error => {
                console.error('Error loading sensor data:', error);
            });
    }

    loadSensorData();
    setInterval(loadSensorData, 5000);
</script>
@endpush