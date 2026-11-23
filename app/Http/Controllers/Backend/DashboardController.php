<?php

namespace App\Http\Controllers\Backend;

use App\Domains\Auth\Models\User;
use App\Services\UintahBasinWeatherService;
use Illuminate\Http\JsonResponse;

class DashboardController
{
    public function index()
    {
        $canViewUsers = auth()->user()->can('admin.access.user.list');

        // Never show administrator accounts in the recent User roster.
        // Keep login timestamps before never-signed-in accounts.
        $recentUsers = $canViewUsers
            ? User::query()
                ->where('type', User::TYPE_USER)
                ->select(['id', 'name', 'type', 'active', 'timezone', 'last_login_at', 'created_at'])
                ->with('roles:id,name')
                ->orderByRaw('last_login_at IS NULL')
                ->orderByDesc('last_login_at')
                ->orderByDesc('created_at')
                ->limit(8)
                ->get()
            : collect();

        // The dashboard HTML is never blocked on external NWS requests.
        return view('backend.dashboard', compact('recentUsers', 'canViewUsers'));
    }

    public function currentWeather(UintahBasinWeatherService $weather): JsonResponse
    {
        $result = $weather->current();

        return response()->json($result, $result['ok'] ? 200 : 503)
            ->header('Cache-Control', 'private, no-store');
    }
}
