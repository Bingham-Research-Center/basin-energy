<?php

namespace App\Http\Controllers\Frontend\User;

/**
 * Class AccountController.
 */
class AccountController
{
    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        if (auth()->user()->isAdmin()) {
            return redirect()->route('admin.account.index');
        }

        return view('frontend.user.account');
    }
}
