<?php

namespace App\Domains\Auth\Http\Requests\Frontend\Auth;

use App\Domains\Auth\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class TwoFactorChallengeRequest extends FormRequest
{
    public function authorize()
    {
        return $this->session()->has('auth.pending_2fa.user_id');
    }

    public function rules()
    {
        return [
            'code' => ['required', 'digits:6'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $userId = $this->session()->get('auth.pending_2fa.user_id');

            if (! $userId) {
                $validator->errors()->add('code', __('Your login session expired. Please sign in again.'));
                return;
            }

            $user = User::find($userId);

            if (! $user || ! $user->hasTwoFactorEnabled()) {
                $validator->errors()->add('code', __('Two-factor authentication is not available for this account.'));
                return;
            }

            if (! $user->validateTwoFactorCode($this->input('code'))) {
                $validator->errors()->add('code', __('The authentication code is invalid.'));
            }
        });
    }
}