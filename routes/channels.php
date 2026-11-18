<?php

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

//Broadcast::channel('App.Domains.Auth.Models.User.{id}', function ($user, $id) {
//    return (int) $user->id === (int) $id;
//});

\Illuminate\Support\Facades\Broadcast::channel('admin.newsletters.{adminId}', function ($user, $adminId) {
    return $user->isAdmin() && $user->isActive() && (int) $user->id === (int) $adminId;
});
