<?php

namespace App\Http\Controllers\Frontend\User;

use App\Domains\Auth\Services\UserService;
use App\Domains\Auth\Support\AccountNavigation;
use App\Http\Requests\Frontend\User\UpdateProfileRequest;

/**
 * Class ProfileController.
 */
class ProfileController
{
    /**
     * @param  UpdateProfileRequest  $request
     * @param  UserService  $userService
     * @return mixed
     */
    public function update(UpdateProfileRequest $request, UserService $userService)
    {
        $userService->updateProfile($request->user(), $request->validated());

        if (session()->has('resent')) {
            if ($request->user()->isAdmin()) {
                return redirect()->to(AccountNavigation::url($request->user(), 'information'))
                    ->withFlashInfo(__('You must confirm your new e-mail address before you can go any further.'));
            }

            return redirect()->route('frontend.auth.verification.notice')
                ->withFlashInfo(__('You must confirm your new e-mail address before you can go any further.'));
        }

        // Legacy admin forms may still submit to the frontend endpoint.
        // Never switch an Administrator into the User account interface.
        return redirect()->to(AccountNavigation::url($request->user(), 'information'))
            ->withFlashSuccess(__('Profile successfully updated.'));
    }
}
