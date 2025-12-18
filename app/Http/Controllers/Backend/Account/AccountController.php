<?php

namespace App\Http\Controllers\Backend\Account;

use App\Http\Controllers\Controller;

class AccountController extends Controller
{
    public function index()
    {
        return view('backend.auth.admin.account');
    }
}
