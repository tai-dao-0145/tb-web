<?php

namespace App\Traits;

use App\Helpers\HttpStatus;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

trait ApiResponse
{
    /**
     * Success Response
     * @param $data
     * @param string|null $msg
     * @param int $code
     * @return JsonResponse
     */
    protected function success(
        $data = null,
        ?string $msg = 'OK',
        int $code = HttpStatus::STATUS_200
    ): JsonResponse {
        $response = [
            'status' => true,
            'message' => $msg,
            'results' => $data
        ];
        return response()->json($response, $code);
    }

    /**
     * Error Response
     * @param string|null $msg
     * @param Throwable|null $e
     * @param int $code
     * @param array $data
     * @return JsonResponse
     */
    protected function error(
        ?string   $msg = 'error',
        Throwable $e = null,
        int       $code = HttpStatus::STATUS_400,
        array     $data = ['type' => 'error']
    ): JsonResponse {
        $response = [
            'status' => false,
            'message' => $msg ?? Response::$statusTexts[$code],
            'errors' => [
                ...$data,
                'message' => $e?->getMessage(),
                'file' => $e?->getFile(),
                'line' => $e?->getLine(),
            ]
        ];
        if ($e) {
            Log::error($e->__toString());
        }
        $code = array_key_exists($code, Response::$statusTexts) ? $code : HttpStatus::STATUS_400;
        return response()->json($response, $code);
    }

    /**
     * Response with status code 201.
     */
    protected function created($data, ?string $msg = 'OK'): JsonResponse
    {
        return $this->success($data, $msg, HttpStatus::STATUS_201);
    }

    /**
     * Response with status code 400.
     */
    protected function badRequest(?string $msg = null, $data = null): JsonResponse
    {
        return $this->error($msg, $data);
    }

    /**
     * Response with status code 401.
     */
    protected function unauthorized(?string $msg = null, $data = null): JsonResponse
    {
        return $this->error($msg, $data, HttpStatus::STATUS_401);
    }

    /**
     * Response with status code 403.
     */
    protected function forbidden(?string $msg = null, $data = null): JsonResponse
    {
        return $this->error($msg, $data, HttpStatus::STATUS_403);
    }

    /**
     * Response with status code 404.
     */
    protected function notFound(?string $msg = null, $data = null): JsonResponse
    {
        return $this->error($msg, $data, HttpStatus::STATUS_404);
    }

    /**
     * Response with status code 404.
     */
    protected function methodNotAllowed(?string $msg = null, $data = null): JsonResponse
    {
        return $this->error($msg, $data, HttpStatus::STATUS_405);
    }

    /**
     * Response with status code 409.
     */
    protected function conflict(?string $msg = null, $data = null): JsonResponse
    {
        return $this->error($msg, $data, HttpStatus::STATUS_409);
    }

    /**
     * Response with status code 400.
     */
    protected function unprocessable(?string $msg = null, $data = null): JsonResponse
    {
        return $this->error($msg, $data);
    }

    /**
     * Response with status code 400.
     */
    protected function internalError(string $msg = 'Internal Error', Throwable $e = null): JsonResponse
    {
        return $this->error($msg, $e);
    }

    /**
     * Respond with bad request.
     */
    protected function tooManyRequests(?string $msg = 'Too Many Requests', Throwable $e = null): JsonResponse
    {
        return $this->error($msg, $e, HttpStatus::STATUS_429);
    }

    protected function validationErrors(ValidationException $e): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $e->getMessage(),
            'errors' => $e->errors(),
        ], HttpStatus::STATUS_422);
    }
}
