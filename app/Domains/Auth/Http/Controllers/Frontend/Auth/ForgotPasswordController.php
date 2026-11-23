<?php

namespace App\Domains\Auth\Http\Controllers\Frontend\Auth;

use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

/**
 * Send password reset links without disclosing whether an email is registered.
 */
class ForgotPasswordController
{
    use SendsPasswordResetEmails {
        sendResetLinkFailedResponse as protected originalResetLinkFailedResponse;
    }

    public function showLinkRequestForm()
    {
        return view('frontend.auth.passwords.email');
    }

    /**
     * Return the same acknowledgement for a registered and unregistered email.
     * For AJAX requests, return JSON so the existing popup can show the message
     * in place without interpreting a successful HTML redirect as a close event.
     */
    protected function sendResetLinkResponse(Request $request, $response)
    {
        $message = __('If an account exists with that email address, you will receive a password reset link shortly. Please check your inbox and spam folder.');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('status', $message);
    }

    /**
     * Invalid email syntax and throttling should still show actionable errors.
     * An unknown account, however, must not be disclosed to the requester.
     */
    protected function sendResetLinkFailedResponse(Request $request, $response)
    {
        if ($response === Password::INVALID_USER) {
            return $this->sendResetLinkResponse($request, $response);
        }

        return $this->originalResetLinkFailedResponse($request, $response);
    }
}
