<?php

use App\Http\Controllers\Backend\Account\AccountController;
use App\Http\Controllers\Backend\Account\ProfileController;

Route::group(['prefix' => 'account', 'as' => 'account.', 'middleware' => [
        config('boilerplate.access.middleware.confirm'),
        // The enclosing "admin" middleware in routes/web.php already verifies
        // the account type. Limited Administrator roles must be able to edit
        // their own profile too; the super-admin role is not required.
        'password.expires'],
], function () {
    Route::get('/', [AccountController::class, 'index'])->name('index');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
});
