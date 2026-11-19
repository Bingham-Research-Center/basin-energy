<?php

use App\Http\Controllers\Backend\Account\AccountController;
use App\Http\Controllers\Backend\Account\ProfileController;
use Tabuna\Breadcrumbs\Trail;

Route::group(['prefix' => 'account', 'as' => 'account.', 'middleware' => [
        config('boilerplate.access.middleware.confirm'),
        // The enclosing "admin" middleware in routes/web.php already verifies
        // the account type. Limited Administrator roles must be able to edit
        // their own profile too; the super-admin role is not required.
        'password.expires'],
], function () {
    Route::get('/', [AccountController::class, 'index'])
        ->name('index')
        ->breadcrumbs(function (Trail $trail) {
            $trail->parent('admin.dashboard')
                ->push(__('Account Info'), route('admin.account.index'));
        });
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
});
