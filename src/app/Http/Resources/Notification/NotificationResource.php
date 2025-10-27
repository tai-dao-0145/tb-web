<?php

namespace App\Http\Resources\Notification;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class NotificationResource extends BaseResource
{
    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'date' => $this['date'],
            'staff_types' => $this['staff_types'],
        ];
    }
}
