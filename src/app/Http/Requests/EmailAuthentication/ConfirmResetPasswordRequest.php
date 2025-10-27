<?php

namespace App\Http\Requests\EmailAuthentication;

use App\Http\Requests\BaseRequest;

class ConfirmResetPasswordRequest extends BaseRequest
{
    /**
     * rulesPost
     * handle rule method post
     *
     * @return array
     */
    public function rulesPost(): array
    {
        return [
            'token' => [
                'required',
                'string',
            ],
            'email' => 'required|email|exists:users,email,deleted_at,NULL',
            'password' => 'required|confirmed|min:8|max:32',
        ];
    }

    /**
     * Custom message for rule
     *
     * @return array
     */
    public function getMessages(): array
    {
        return [];
    }
}
