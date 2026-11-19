<?php

use App\Http\Controllers\Backend\BroadcastController;
use App\Models\BroadcastAnnouncement;
use App\Models\BroadcastNewsletter;
use Tabuna\Breadcrumbs\Trail;

Route::prefix('broadcast')->as('broadcast.')->group(function () {
    Route::post('editor/autosave/{kind}', [\App\Http\Controllers\Backend\BroadcastEditorController::class, 'autosave'])
        ->middleware('throttle:120,1')->name('editor.autosave');
    Route::post('editor/images', [\App\Http\Controllers\Backend\BroadcastEditorController::class, 'uploadImage'])
        ->middleware('throttle:30,1')->name('editor.images');
    Route::redirect('/', '/admin/broadcast/newsletters')
        ->name('index')
        ->breadcrumbs(function (Trail $trail) {
            $trail->parent('admin.dashboard')
                ->push(__('Broadcast'), route('admin.broadcast.index'));
        });

    Route::get('newsletters', [BroadcastController::class, 'newsletters'])
        ->name('newsletters.index')
        ->breadcrumbs(function (Trail $trail) {
            $trail->parent('admin.broadcast.index')
                ->push(__('Newsletters'), route('admin.broadcast.newsletters.index'));
        });
    Route::get('newsletters/create', [BroadcastController::class, 'createNewsletter'])
        ->name('newsletters.create')
        ->breadcrumbs(function (Trail $trail) {
            $trail->parent('admin.broadcast.newsletters.index')
                ->push(__('Create Newsletter'), route('admin.broadcast.newsletters.create'));
        });
    Route::post('newsletters', [BroadcastController::class, 'storeNewsletter'])->name('newsletters.store');
    Route::get('newsletters/{newsletter}/edit', [BroadcastController::class, 'editNewsletter'])
        ->name('newsletters.edit')
        ->breadcrumbs(function (Trail $trail, BroadcastNewsletter $newsletter) {
            $trail->parent('admin.broadcast.newsletters.index')
                ->push($newsletter->title, route('admin.broadcast.newsletters.edit', $newsletter))
                ->push(__('Edit'), route('admin.broadcast.newsletters.edit', $newsletter));
        });
    Route::put('newsletters/{newsletter}', [BroadcastController::class, 'updateNewsletter'])->name('newsletters.update');
    Route::post('newsletters/{newsletter}/schedule', [BroadcastController::class, 'scheduleNewsletter'])->name('newsletters.schedule');
    Route::delete('newsletters/{newsletter}', [BroadcastController::class, 'deleteNewsletter'])->name('newsletters.delete');

    Route::get('announcements', [BroadcastController::class, 'announcements'])
        ->name('announcements.index')
        ->breadcrumbs(function (Trail $trail) {
            $trail->parent('admin.broadcast.index')
                ->push(__('Announcements'), route('admin.broadcast.announcements.index'));
        });
    Route::get('announcements/create', [BroadcastController::class, 'createAnnouncement'])
        ->name('announcements.create')
        ->breadcrumbs(function (Trail $trail) {
            $trail->parent('admin.broadcast.announcements.index')
                ->push(__('Create Announcement'), route('admin.broadcast.announcements.create'));
        });
    Route::post('announcements', [BroadcastController::class, 'storeAnnouncement'])->name('announcements.store');
    Route::get('announcements/{announcement}/edit', [BroadcastController::class, 'editAnnouncement'])
        ->name('announcements.edit')
        ->breadcrumbs(function (Trail $trail, BroadcastAnnouncement $announcement) {
            $trail->parent('admin.broadcast.announcements.index')
                ->push($announcement->title, route('admin.broadcast.announcements.edit', $announcement))
                ->push(__('Edit'), route('admin.broadcast.announcements.edit', $announcement));
        });
    Route::put('announcements/{announcement}', [BroadcastController::class, 'updateAnnouncement'])->name('announcements.update');
    Route::post('announcements/{announcement}/publish', [BroadcastController::class, 'publishAnnouncement'])->name('announcements.publish');
    Route::delete('announcements/{announcement}', [BroadcastController::class, 'deleteAnnouncement'])->name('announcements.delete');

    Route::get('alerts', [\App\Http\Controllers\Backend\DashboardNotificationController::class, 'index'])
        ->middleware('throttle:60,1')->name('alerts');
    Route::post('alerts/read', [\App\Http\Controllers\Backend\DashboardNotificationController::class, 'markAllRead'])
        ->middleware('throttle:30,1')->name('alerts.read');
});
