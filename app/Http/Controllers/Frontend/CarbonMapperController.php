<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\CarbonMapperObservation;
use Illuminate\Http\Request;

class CarbonMapperController extends Controller
{
    /**
     * Page view
     */
    public function utah()
    {
        return view('frontend.carbon-mapper.index');
    }

    /**
     * JSON endpoint (GLOBAL data)
     */
    public function utahJson(Request $request)
    {
        $query = CarbonMapperObservation::query()
            ->where('gas', 'CH4')
            ->select([
                'latitude',
                'longitude',
                'observed_at',
            ]);

        if ($request->filled('bbox')) {
            [$minLon, $minLat, $maxLon, $maxLat] = explode(',', $request->bbox);
            $query
                ->whereBetween('longitude', [$minLon, $maxLon])
                ->whereBetween('latitude', [$minLat, $maxLat]);
        }

        if ($request->filled('from')) {
            $query->where('observed_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->where('observed_at', '<=', $request->to);
        }

        return response()->json(
            $query->limit(20000)->get()
        );
    }


    public function utahDetections()
    {
        $plumes = CarbonMapperObservation::query()
            ->where('gas', 'CH4')
            ->whereBetween('longitude', [-114.05, -109.05])
            ->whereBetween('latitude', [36.99, 42.00])
            ->select([
                'id',
                'latitude',
                'longitude',
                'observed_at',
            ])
            ->get();

        return response()->json($plumes);
    }

    public function utahSources()
    {
        return CarbonMapperObservation::where('gas','CH4')
            ->whereBetween('longitude', [-114.05, -109.05])
            ->whereBetween('latitude', [36.99, 42])
            ->selectRaw('
                ROUND(latitude, 3) as latitude,
                ROUND(longitude, 3) as longitude,
                COUNT(*) as observations,
                MAX(observed_at) as last_seen
            ')
            ->groupBy('latitude','longitude')
            ->having('observations', '>=', 5)
            ->get();
    }


}
