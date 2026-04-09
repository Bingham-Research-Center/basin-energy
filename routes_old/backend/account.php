<?php

use App\Http\Controllers\Backend\Account\AccountController;
use App\Http\Controllers\Backend\Account\ProfileController;

Route::group(['prefix' => 'account', 'as' => 'account.', 'middleware' => [
        config('boilerplate.access.middleware.confirm'),
        'role:' . config('boilerplate.access.role.admin'),
        'password.expires'],
], function () {
    Route::get('/', [AccountController::class, 'index'])->name('index');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
});
