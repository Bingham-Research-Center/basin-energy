<?php

namespace App\Http\Controllers\Backend;

use App\Models\ContactMessage;
use App\Models\NewsletterAdminAlert;
use App\Models\UserRegistrationAdminAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DashboardNotificationController
{
    private const PAGE_SIZE = 5;

    private const MAX_PAGE = 20;

    /**
     * A single, bounded feed for Contact, Newsletter and User registration alerts.
     *
     * Contact messages use their existing global read state. Newsletter
     * subscription alerts are always scoped to the current administrator.
     */
    public function index(Request $request): JsonResponse
    {
        $page = filter_var($request->query('page', 1), FILTER_VALIDATE_INT);
        $page = $page === false ? 1 : max(1, min(self::MAX_PAGE, $page));
        $fetchLimit = self::PAGE_SIZE * $page + 1;
        $adminId = $request->user()->id;

        $contactCount = ContactMessage::where('is_read', false)->count();
        $newsletterCount = NewsletterAdminAlert::where('admin_id', $adminId)
            ->whereNull('read_at')->count();

        $canViewUsers = $request->user()->can('admin.access.user.list');
        $registrationCount = $canViewUsers
            ? UserRegistrationAdminAlert::where('admin_id', $adminId)
                ->whereNull('read_at')->count()
            : 0;

        $contacts = ContactMessage::query()->latest('created_at')->latest('id')
            ->limit($fetchLimit)->get()->map(function (ContactMessage $message) {
                return [
                    'id' => $message->id,
                    'type' => 'contact',
                    'title' => $message->name.' sent a message',
                    'body' => Str::limit($message->message, 190),
                    'created_at' => optional($message->created_at)->toIso8601String(),
                    'time' => optional($message->created_at)->timestamp ?? 0,
                    'url' => route('admin.website-update.contact'),
                    'unread' => ! $message->is_read,
                ];
            })->all();

        $subscriptions = NewsletterAdminAlert::query()->where('admin_id', $adminId)
            ->with('subscriber:id,name')->latest('created_at')->latest('id')
            ->limit($fetchLimit)->get()->map(function (NewsletterAdminAlert $alert) {
                return [
                    'id' => $alert->id,
                    'type' => 'subscription',
                    'title' => ($alert->subscriber->name ?? __('Deleted user')).' subscribed',
                    'body' => __('Subscribed to the monthly newsletter.'),
                    'created_at' => optional($alert->created_at)->toIso8601String(),
                    'time' => optional($alert->created_at)->timestamp ?? 0,
                    'url' => route('admin.broadcast.newsletters.index'),
                    'unread' => $alert->read_at === null,
                ];
            })->all();

        $registrations = $canViewUsers
            ? UserRegistrationAdminAlert::query()
                ->where('admin_id', $adminId)
                ->with('user:id,name,provider')
                ->latest('created_at')->latest('id')
                ->limit($fetchLimit)->get()->map(function (UserRegistrationAdminAlert $alert) {
                    $user = $alert->user;

                    return [
                        'id' => $alert->id,
                        'type' => 'registration',
                        'title' => ($user->name ?? __('Deleted user')).' registered',
                        'body' => $user && $user->provider === 'google'
                            ? __('New account registered with Google.')
                            : __('New account registered with email.'),
                        'created_at' => optional($alert->created_at)->toIso8601String(),
                        'time' => optional($alert->created_at)->timestamp ?? 0,
                        'url' => $user
                            ? route('admin.auth.user.show', ['user' => $user->id])
                            : route('admin.auth.user.index'),
                        'unread' => $alert->read_at === null,
                    ];
                })->all()
            : [];

        $all = array_merge($contacts, $subscriptions, $registrations);
        usort($all, static function (array $a, array $b): int {
            return ($b['time'] <=> $a['time'])
                ?: ($b['id'] <=> $a['id'])
                ?: strcmp($b['type'], $a['type']);
        });

        $offset = ($page - 1) * self::PAGE_SIZE;
        $pageItems = array_slice($all, $offset, self::PAGE_SIZE);

        return response()->json([
            'count' => $contactCount + $newsletterCount + $registrationCount,
            'contact_count' => $contactCount,
            'newsletter_count' => $newsletterCount,
            'registration_count' => $registrationCount,
            'items' => array_map(static function (array $item): array {
                unset($item['time']);
                return $item;
            }, $pageItems),
            'page' => $page,
            'has_more' => $page < self::MAX_PAGE && count($all) > $offset + self::PAGE_SIZE,
        ])->header('Cache-Control', 'private, no-store');
    }

    /**
     * Mark all notification sources read without a redirect for
     * AJAX clients. Keep POST form compatibility for older browser sessions.
     */
    public function markAllRead(Request $request)
    {
        $updated = DB::transaction(function () use ($request) {
            $newsletter = NewsletterAdminAlert::where('admin_id', $request->user()->id)
                ->whereNull('read_at')->update(['read_at' => now()]);
            $contacts = ContactMessage::where('is_read', false)->update(['is_read' => true]);
            $registrations = $request->user()->can('admin.access.user.list')
                ? UserRegistrationAdminAlert::where('admin_id', $request->user()->id)
                    ->whereNull('read_at')->update(['read_at' => now()])
                : 0;

            return $newsletter + $contacts + $registrations;
        });

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'marked' => $updated,
                'count' => 0,
                'message' => __('All notifications marked as read.'),
            ])->header('Cache-Control', 'private, no-store');
        }

        return redirect()->route('admin.broadcast.newsletters.index')
            ->withFlashSuccess(__('All notifications marked as read.'));
    }
}
