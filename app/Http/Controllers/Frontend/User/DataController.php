<?php

namespace App\Http\Controllers\Frontend\User;

use App\Http\Controllers\Controller;
use App\Models\EmissionTrend;
use Illuminate\Http\Request;

class DataController extends Controller
{
    public function emission_trends()
    {
        $rows = EmissionTrend::orderBy('year', 'asc')->paginate(25);

        return view('frontend.user.data.emission_trends', compact('rows'));
    }

    public function emission_trends_json(Request $request)
    {
        // Allowed columns user can plot (prevents arbitrary column access)
        $columns = [
            'basinwide_ch4_emiss_mg_hr' => 'CH4 (Mg/hr)',
            'oil_million_bbls'          => 'Oil (MM bbl)',
            'gas_million_bbleq'         => 'Gas (MM bbleq)',
            'energy_million_bbleq'      => 'Energy (MM bbleq)',
            'emisco2eq20_millnmg'       => 'CO2e20 (million Mg)',
            'producing_wells'           => 'Producing wells',
            'newwells'                  => 'New wells',
            'oil_gas_ratio'             => 'Oil/Gas ratio',
            'utahgasprice_dollpermcf'   => 'Utah gas price ($/MCF)',
            'utahcrudeprice_dollperbbl' => 'Utah crude price ($/BBL)',
        ];

        $selected = $request->input('cols', []);
        $selected = array_values(array_intersect($selected, array_keys($columns)));

        // Default if none selected
        if (count($selected) === 0) {
            $selected = ['basinwide_ch4_emiss_mg_hr'];
        }

        $rows = EmissionTrend::query()
            ->select(array_merge(['year'], $selected))
            ->orderBy('year')
            ->get();

        return response()->json([
            'labels' => $rows->pluck('year')->values(),
            'series' => collect($selected)->map(function ($col) use ($rows, $columns) {
                return [
                    'key'   => $col,
                    'label' => $columns[$col],
                    'data'  => $rows->pluck($col)->map(fn($v) => $v === null ? null : (float)$v)->values(),
                ];
            })->values(),
            'available' => collect($columns)->map(fn($label, $key) => [
                'key' => $key,
                'label' => $label,
            ])->values(),
        ]);
    }
}
