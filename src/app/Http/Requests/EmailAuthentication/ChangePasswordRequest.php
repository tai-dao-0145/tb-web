<?php

namespace App\Http\Requests\EmailAuthentication;

use App\Http\Requests\BaseRequest;

class ChangePasswordRequest extends BaseRequest
{
    /**
     * rulesPost
     * handle rule method post
     *
     * @return array
     */
    public function rulesPatch(): array
    {
        return [
            'current_password' => 'required|string|min:6',
            'new_password' => 'required|string|min:8|max:32',
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
