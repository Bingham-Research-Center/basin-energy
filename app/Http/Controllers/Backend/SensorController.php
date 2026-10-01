<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;

class SensorController extends Controller
{
    public function index()
    {
        return view('backend.sensors.sensor-dashboard');
    }
}