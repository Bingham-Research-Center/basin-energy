<?php

namespace App\Domains\Auth\Http\Controllers\Backend\User;

use App\Domains\Auth\Models\User;
use App\Domains\Auth\Services\UserService;
use Illuminate\Http\Request;

/**
 * Class UserStatusController.
 */
class DeactivatedUserController
{
    /**
     * @var UserService
     */
    protected $userService;

    /**
     * DeactivatedUserController constructor.
     *
     * @param  UserService  $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view('backend.auth.user.deactivated');
    }

    /**
     * @param  Request  $request
     * @param  User  $user
     * @param $status
     * @return mixed
     *
     * @throws \App\Exceptions\GeneralException
     */
    public function update(Request $request, User $user, $status)
    {
        $status = (int) $status;
        $requiredPermission = $status === 1
            ? 'admin.access.user.reactivate'
            : 'admin.access.user.deactivate';

        // The route-level OR middleware permits entry for either action.
        // Enforce the permission for the specific requested transition.
        abort_unless($request->user()->can($requiredPermission), 403);

        $this->userService->mark($user, $status);

        // Do not send a Reactivate-only operator to the general user index,
        // which intentionally requires separate permissions.
        if ($status === 1) {
            $destination = $request->user()->can('admin.access.user.list')
                ? 'admin.auth.user.index'
                : 'admin.auth.user.deactivated';
        } else {
            $destination = $request->user()->can('admin.access.user.reactivate')
                ? 'admin.auth.user.deactivated'
                : 'admin.auth.user.index';
        }

        return redirect()->route($destination)
            ->withFlashSuccess(__('The user was successfully updated.'));
    }
}
