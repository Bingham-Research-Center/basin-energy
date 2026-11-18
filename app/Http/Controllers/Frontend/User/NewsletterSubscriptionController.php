<?php

namespace App\Http\Controllers\Frontend\User;

use App\Domains\Auth\Models\User;
use App\Events\NewsletterSubscriptionChanged;
use App\Models\BroadcastAnnouncementDelivery;
use App\Models\NewsletterAdminAlert;
use App\Models\NewsletterSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NewsletterSubscriptionController
{
    public function update(Request $request)
    {
        abort_unless($request->user()->isUser(), 403);

        $changed = DB::transaction(function () use ($request) {
            // Lock the User row, which always exists. This serializes the
            // first-time subscription flow when no subscription row exists.
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $row = NewsletterSubscription::where('user_id', $request->user()->id)
                ->lockForUpdate()->first();

            if ($row && $row->is_subscribed) {
                return false;
            }

            NewsletterSubscription::updateOrCreate(
                ['user_id' => $request->user()->id],
                ['subscribed_at' => now(), 'unsubscribed_at' => null]
            );

            // Each admin gets an independent, persistent read marker.
            User::query()->where('type', User::TYPE_ADMIN)->where('active', true)
                ->select('id')->chunkById(200, function ($admins) use ($request) {
                    foreach ($admins as $admin) {
                        NewsletterAdminAlert::create([
                            'admin_id' => $admin->id,
                            'subscriber_id' => $request->user()->id,
                        ]);
                    }
                });

            return true;
        });

        if ($changed) {
            event(new NewsletterSubscriptionChanged($request->user()));
        }

        return back()->withFlashSuccess(
            $changed ? __('You are subscribed to the monthly newsletter.')
                : __('You are already subscribed to the monthly newsletter.')
        );
    }

    public function destroy(Request $request)
    {
        abort_unless($request->user()->isUser(), 403);

        NewsletterSubscription::where('user_id', $request->user()->id)
            ->whereNull('unsubscribed_at')->update(['unsubscribed_at' => now()]);

        return back()->withFlashSuccess(__('You have unsubscribed from the monthly newsletter.'));
    }

    public function announcements(Request $request)
    {
        abort_unless($request->user()->isUser(), 403);

        $deliveries = BroadcastAnnouncementDelivery::query()
            ->where('user_id', $request->user()->id)
            ->with('announcement')
            ->latest('id')->paginate(15);

        return view('frontend.user.broadcast.announcements', compact('deliveries'));
    }

    public function confirmEmailUnsubscribe(Request $request, User $user)
    {
        abort_unless($user->isUser(), 404);

        return view('frontend.user.broadcast.unsubscribe', ['unsubscribeUser' => $user]);
    }

    public function emailUnsubscribe(Request $request, User $user)
    {
        abort_unless($user->isUser(), 404);

        NewsletterSubscription::where('user_id', $user->id)
            ->whereNull('unsubscribed_at')->update(['unsubscribed_at' => now()]);

        return view('frontend.user.broadcast.unsubscribed');
    }
}
