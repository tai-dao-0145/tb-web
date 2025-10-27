<?php

namespace App\Exceptions;

use App\Enums\Log\SystemLogLevelEnum;
use App\Helpers\HttpStatus;
use App\Jobs\SendErrorMailJob;
use App\Models\SystemLog;
use App\Traits\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Sentry\Laravel\Integration;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    use ApiResponse;

    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            Integration::captureUnhandledException($e);
        });
    }

    /**
     * Register the exception handling callbacks for the application.
     *
     * @param Request $request
     *
     * @throws Throwable
     */
    public function render($request, Throwable $e): JsonResponse|Response
    {
        if ($request->wantsJson() || $request->expectsJson()) {
            return $this->handleApiException($e);
        }
        return parent::render($request, $e);
    }

    /**
     * @param Throwable $e
     * @return JsonResponse
     */
    private function handleApiException(Throwable $e): JsonResponse
    {
        $e = $this->prepareException($e);

        if ($e instanceof AuthenticationException) {
            return $this->unauthorized($e->getMessage());
        }
        if ($e instanceof UnauthorizedException) {
            return $this->forbidden($e->getMessage());
        }
        if ($e instanceof ValidationException) {
            return $this->validationErrors($e);
        }
        if ($e instanceof ModelNotFoundException) {
            return $this->notFound('Entry for ' . $e->getModel() . ' not found');
        }
        if ($e instanceof PostTooLargeException) {
            return $this->badRequest('Size of attached file too large');
        }
        if ($e instanceof InvalidArgumentException) {
            return $this->badRequest('Invalid Argument');
        }
        if ($e instanceof ThrottleRequestsException) {
            return $this->tooManyRequests('Too Many Requests', $e);
        }
        if ($e instanceof QueryException) {
            return $this->internalError('There was an issue with the query', $e);
        }
        if ($e instanceof NotFoundHttpException) {
            return $this->notFound('Not Found', $e);
        }
        if ($e instanceof MethodNotAllowedHttpException) {
            return $this->methodNotAllowed('Method Not Allowed', $e);
        }
        if ($e instanceof HttpException) {
            if ($e->getStatusCode() === HttpStatus::STATUS_403) {
                return $this->forbidden($e->getMessage());
            }
        }
        return $this->internalError('There was an internal exception', $e);
    }
}
