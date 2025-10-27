<?php

namespace App\Http\Requests\EmailAuthentication;

use App\Http\Requests\BaseRequest;
use App\Models\User;
use App\Repositories\Interface\IUserRepository;
use Illuminate\Validation\ValidationException;
use Throwable;

class ResetPasswordRequest extends BaseRequest
{
    protected ?string $resolvedEmail = null;

    /**
     * rulesPost
     * handle rule method post
     *
     * @return array
     */
    public function rulesPost(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return void
     * @throws ValidationException
     */
    protected function passedValidation(): void
    {
        $userRepository = app(IUserRepository::class);
        $identifier = $this->input('identifier');
        $email = $identifier;

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $user = $userRepository->filterOne([
                'email' => $email,
            ]);

            if (! $user) {
                throw ValidationException::withMessages([
                    'identifier' => [__('message.user_id_or_email_incorrect')],
                ]);
            }

            $this->resolvedEmail = $user->email;
            return;
        }

        $email = $identifier . config('const.system_email_domain');
        try {
            $firebaseUser = app('firebase.auth')->getUserByEmail($email);
            $firebaseUid = $firebaseUser->uid;
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'identifier' => [__('message.user_id_or_email_incorrect')],
            ]);
        }

        $user = $userRepository->filterOne([
            'firebase_uid' => $firebaseUid,
        ]);

        if (! $user) {
            throw ValidationException::withMessages([
                'identifier' => [__('message.user_id_or_email_incorrect')],
            ]);
        }

        $this->resolvedEmail = $user->email;
    }

    /**
     * @return string
     */
    public function getResolvedEmail(): string
    {
        return $this->resolvedEmail;
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
