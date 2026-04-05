<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TestSensorReading;
use App\Events\TestSensorReadingCreated;
use Illuminate\Http\Request;

class TestSensorReadingController extends Controller
{
    public function store(Request $request)
    {
        if ($request->api_key !== env('SENSOR_API_KEY')) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $validated = $request->validate([
            'device_id'   => 'nullable|string|max:255',
            'temperature' => 'required|numeric',
            'humidity'    => 'required|numeric',
            'recorded_at' => 'nullable|date',
        ]);

        $reading = TestSensorReading::create([
            'device_id'   => $validated['device_id'] ?? 'default-device',
            'temperature' => $validated['temperature'],
            'humidity'    => $validated['humidity'],
            'recorded_at' => $validated['recorded_at'] ?? now(),
        ]);

        broadcast(new TestSensorReadingCreated($reading))->toOthers();

        return response()->json([
            'status' => true,
            'message' => 'Reading stored successfully',
            'data' => $reading,
        ]);
    }

    public function latest()
    {
        $readings = TestSensorReading::latest('recorded_at')
            ->take(30)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'status' => true,
            'data' => $readings,
        ]);
    }
}