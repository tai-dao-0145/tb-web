<?php

namespace App\Http\Resources;

use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

class BaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     *
     * @return array
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }

    /**
     * @param mixed $resource
     * @return array|AnonymousResourceCollection
     */
    public static function collection($resource): AnonymousResourceCollection|array
    {
        $result = parent::collection($resource);
        if ($resource instanceof LengthAwarePaginator || $resource instanceof Paginator) {
            return ResponseHelper::responsePaginate($result, $resource);
        }
        if($resource instanceof Collection){
            return ResponseHelper::responseWithoutPaginate($result);
        }
        return $result;
    }

    /**
     * @param mixed $resource
     * @return array|AnonymousResourceCollection
     */
    public static function directCollection(mixed $resource): AnonymousResourceCollection|array
    {
        return parent::collection($resource);
    }
}
