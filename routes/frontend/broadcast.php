<?php

use App\Http\Controllers\Frontend\User\NewsletterSubscriptionController;
use Tabuna\Breadcrumbs\Trail;

Route::middleware(['auth', '2fa.confirmed', 'password.expires', config('boilerplate.access.middleware.verified'), 'is_user'])
    ->prefix('broadcast')->as('broadcast.')->group(function () {
        Route::post('newsletter/subscribe', [NewsletterSubscriptionController::class, 'update'])
            ->middleware('throttle:10,1')->name('newsletter.subscribe');
        Route::delete('newsletter/subscribe', [NewsletterSubscriptionController::class, 'destroy'])
            ->name('newsletter.unsubscribe');
        Route::get('announcements', [NewsletterSubscriptionController::class, 'announcements'])
            ->name('announcements')
            ->breadcrumbs(function (Trail $trail) {
                $trail->parent('frontend.user.dashboard')
                    ->push(__('My Announcements'), route('frontend.broadcast.announcements'));
            });
    });
