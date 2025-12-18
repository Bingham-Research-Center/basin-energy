<?php

namespace App\Http\Controllers\Backend\Account;

use App\Http\Controllers\Controller;
use App\Domains\Auth\Services\UserService;
use App\Http\Requests\Frontend\User\UpdateProfileRequest;

class ProfileController extends Controller
{
    public function update(UpdateProfileRequest $request, UserService $userService)
    {
        $userService->updateProfile($request->user(), $request->validated());

        // If you enforce email verification for admins and you have a backend verification notice route,
        // redirect there. Otherwise just redirect back to the account page.
        if (session()->has('resent')) {
            // OPTION A (recommended if you have a backend verification notice):
            // return redirect()->route('admin.verification.notice')
            //     ->withFlashInfo(__('You must confirm your new e-mail address before you can go any further.'));

            // OPTION B (safe fallback):
            return redirect()
                ->route('admin.account.index')
                ->withFlashInfo(__('You must confirm your new e-mail address before you can go any further.'));
        }

        // Redirect to the backend account page, and keep the tab anchor
        return redirect()
            ->to(route('admin.account.index') . '#information')
            ->withFlashSuccess(__('Profile successfully updated.'));
    }
}
