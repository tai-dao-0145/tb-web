<?php

namespace App\Http\Middleware;

use App\Exceptions\CustomizeException;
use Closure;
use Throwable;
use Exception;
use App\Models\User;
use App\Helpers\HttpStatus;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Illuminate\Support\Facades\Auth;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;

class FirebaseAuthMiddleware
{
    use ApiResponse;

    public const INVALID_CREDENTIALS = 'invalid_credentials';

    private readonly FirebaseAuth $auth;

    public function __construct()
    {
        $this->auth = app('firebase.auth');
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws Throwable
     */
    public function handle(Request $request, Closure $next, string $checkActive = null): mixed
    {
        $idToken = $request->bearerToken();
        if (!$idToken) {
            return $this->error(__('message.no_token_provided'), null, HttpStatus::STATUS_401);
        }
        try {
            $verifiedIdToken = $this->auth->verifyIdToken($idToken);
            $firebaseId = $verifiedIdToken->claims()->get('sub');
            $this->getAccount($request, $firebaseId, $checkActive !== null);
        } catch (FailedToVerifyToken $e) {
            return $this->error(__('message.invalid_or_expired_token'), $e, HttpStatus::STATUS_401);
        } catch (Exception|InvalidArgumentException|FirebaseAuth $e) {
            return $this->error($e->getMessage(), $e, (int)$e->getCode() ?? HttpStatus::STATUS_401);
        }
        return $next($request);
    }

    /**
     * Get user account by firebase id.
     *
     * @param Request $request     request
     * @param string  $firebaseId  firebase id
     * @param bool    $checkActive check active
     *
     * @return void  void
     * @throws Throwable throw exception
     */
    private function getAccount(Request $request, string $firebaseId, bool $checkActive = false): void
    {
        $account = User::query()
            ->with('role')
            ->where('firebase_uid', $firebaseId)
            ->when($checkActive, fn ($q) => $q->where('status', true))
            ->first();

        throw_if(
            empty($account),
            new CustomizeException(__('auth.failed'), HttpStatus::STATUS_400, [
                'type' => self::INVALID_CREDENTIALS
            ])
        );
        throw_if(
            empty($account->role->name),
            new Exception(__('message.invalid_role'), HttpStatus::STATUS_400)
        );

        $account->role = $account->role->name;

        Auth::login($account);
    }
}
