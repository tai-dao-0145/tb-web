<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseRequest;

class ReportLoginRequest extends BaseRequest
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
            'email' => 'required|email|string|max:255',
            'is_success' => 'required|boolean',
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
