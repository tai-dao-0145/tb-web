<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

class ResponseHelper
{
    /**
     * @param int $code
     * @param null $data
     * @param null $message
     * @param null $messageContent
     *
     * @return JsonResponse
     */
    public static function responseJson(
        int $code = HttpStatus::STATUS_200,
            $data = null,
            $message = null,
            $messageContent = null
    ): JsonResponse
    {
        $return = [];
        $return['code'] = $code;
        if ($message) {
            $return['message'] = $message;
        }
        if ($messageContent) {
            $return['message_content'] = $messageContent;
        }
        $return['data'] = $data;
        return response()->json($return);
    }

    /**
     * @param null $code
     * @param null $message
     * @param null $messageContent
     * @param null $internalMessage
     * @param null $dataError
     *
     * @return JsonResponse
     */
    public static function responseJsonError(
        $code = null,
        $message = null,
        $messageContent = null,
        $internalMessage = null,
        $dataError = null): JsonResponse
    {
        $codeStatus = ($code && $code > HttpStatus::STATUS_0) ? $code : HttpStatus::STATUS_400;
        return response()->json(
            [
                'code' => $codeStatus,
                'message' => $message ?? trans('errors.something_error'),
                'message_content' => $messageContent,
                'message_internal' => $internalMessage,
                'data_error' => $dataError,
            ],
            ($code && $code > HttpStatus::STATUS_0) ? $code : HttpStatus::STATUS_400
        );
    }

    /**
     * @param $result
     * @param Paginator|LengthAwarePaginator $resource
     *
     * @return array
     */
    public static function responsePaginate($result, Paginator|LengthAwarePaginator $resource): array
    {
        return [
            'data' => $result,
            'pagination' => [
                'total' => (int)$resource->total(),
                'per_page' => (int)$resource->perPage(),
                'current_page' => (int)$resource->currentPage(),
                'last_page' => (int)$resource->lastPage(),
            ]
        ];
    }

    /**
     * @param $result
     *
     * @return array
     */
    public static function responseWithoutPaginate($result): array
    {
        return ['data' => $result];
    }
}
