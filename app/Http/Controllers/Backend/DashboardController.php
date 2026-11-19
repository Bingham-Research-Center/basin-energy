<?php

namespace App\Http\Controllers\Backend;

use App\Domains\Auth\Models\User;
use App\Models\BroadcastAnnouncement;

class DashboardController
{
    public function index()
    {
        $canViewUsers = auth()->user()->can('admin.access.user.list');

        $stats = $canViewUsers ? [
            'registered_users' => User::query()->where('type', User::TYPE_USER)->count(),
            'active_users' => User::query()
                ->where('type', User::TYPE_USER)->where('active', true)->count(),
            'unverified_users' => User::query()
                ->where('type', User::TYPE_USER)->where('active', true)
                ->whereNull('email_verified_at')->count(),
        ] : [];

        $publishedAnnouncements = BroadcastAnnouncement::query()
            ->where('status', BroadcastAnnouncement::PUBLISHED)
            ->count();

        // The admin overview is deliberately small and read-only.
        // Keep records with a known login ahead of never-signed-in accounts.
        $recentUsers = $canViewUsers
            ? User::query()
                ->select(['id', 'name', 'type', 'active', 'timezone', 'last_login_at', 'created_at'])
                ->with('roles:id,name')
                ->orderByRaw('last_login_at IS NULL')
                ->orderByDesc('last_login_at')
                ->orderByDesc('created_at')
                ->limit(8)
                ->get()
            : collect();

        return view('backend.dashboard', compact(
            'stats', 'recentUsers', 'canViewUsers', 'publishedAnnouncements'
        ));
    }
}
