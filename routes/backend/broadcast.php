<?php

use App\Http\Controllers\Backend\BroadcastController;

Route::prefix('broadcast')->as('broadcast.')->group(function () {
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

    Route::get('alerts', [BroadcastController::class, 'alerts'])->middleware('throttle:60,1')->name('alerts');
    Route::post('alerts/read', [BroadcastController::class, 'markAlertsRead'])->name('alerts.read');
});
