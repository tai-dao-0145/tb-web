<?php

namespace App\Http\Resources\NotificationDashboard;

use App\Enums\AppEnum;
use App\Helpers\Common;
use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class NotificationDashboardResource extends BaseResource
{
    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'date' => Common::formatToDateTime($this->created_at, AppEnum::DATE_FORMAT),
            'message' => $this->message,
        ];
    }
}
