<?php

use App\Http\Controllers\Backend\BroadcastController;

Route::prefix('broadcast')->as('broadcast.')->group(function () {
    Route::post('editor/autosave/{kind}', [\App\Http\Controllers\Backend\BroadcastEditorController::class, 'autosave'])
        ->middleware('throttle:120,1')->name('editor.autosave');
    Route::post('editor/images', [\App\Http\Controllers\Backend\BroadcastEditorController::class, 'uploadImage'])
        ->middleware('throttle:30,1')->name('editor.images');
    Route::redirect('/', '/admin/broadcast/newsletters')->name('index');

    Route::get('newsletters', [BroadcastController::class, 'newsletters'])->name('newsletters.index');
    Route::get('newsletters/create', [BroadcastController::class, 'createNewsletter'])->name('newsletters.create');
    Route::post('newsletters', [BroadcastController::class, 'storeNewsletter'])->name('newsletters.store');
    Route::get('newsletters/{newsletter}/edit', [BroadcastController::class, 'editNewsletter'])->name('newsletters.edit');
    Route::put('newsletters/{newsletter}', [BroadcastController::class, 'updateNewsletter'])->name('newsletters.update');
    Route::post('newsletters/{newsletter}/schedule', [BroadcastController::class, 'scheduleNewsletter'])->name('newsletters.schedule');
    Route::delete('newsletters/{newsletter}', [BroadcastController::class, 'deleteNewsletter'])->name('newsletters.delete');

    Route::get('announcements', [BroadcastController::class, 'announcements'])->name('announcements.index');
    Route::get('announcements/create', [BroadcastController::class, 'createAnnouncement'])->name('announcements.create');
    Route::post('announcements', [BroadcastController::class, 'storeAnnouncement'])->name('announcements.store');
    Route::get('announcements/{announcement}/edit', [BroadcastController::class, 'editAnnouncement'])->name('announcements.edit');
    Route::put('announcements/{announcement}', [BroadcastController::class, 'updateAnnouncement'])->name('announcements.update');
    Route::post('announcements/{announcement}/publish', [BroadcastController::class, 'publishAnnouncement'])->name('announcements.publish');
    Route::delete('announcements/{announcement}', [BroadcastController::class, 'deleteAnnouncement'])->name('announcements.delete');

    Route::get('alerts', [\App\Http\Controllers\Backend\DashboardNotificationController::class, 'index'])
        ->middleware('throttle:60,1')->name('alerts');
    Route::post('alerts/read', [\App\Http\Controllers\Backend\DashboardNotificationController::class, 'markAllRead'])
        ->middleware('throttle:30,1')->name('alerts.read');
});
