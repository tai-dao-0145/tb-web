<?php

namespace App\Http\Requests\CarePlanComments;

use App\Http\Requests\BaseRequest;

class UpdateCarePlanCommentRequest extends BaseRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'comment' => 'bail|required|string|max:'.config('const.maximum_length_for_care_plan_comment'),
            'attachments' => 'bail|array|max:'.config('const.maximum_attachments_for_care_plan_comment'),
            'attachments.*.file_name' => 'bail|required|string|max:'.config('const.maximum_string_length'),
            'attachments.*.file_type' => 'bail|required|string|max:'.config('const.maximum_string_length'),
            'attachments.*.file_size' => 'bail|required|integer|max:'.config('const.maximum_integer_value'),
            'attachments.*.file_path' => 'bail|nullable|string|max:'.config('const.maximum_string_length'),
            'attachments.*.file_url' => 'bail|required|string|max:'.config('const.maximum_string_length'),
            'removed_attachments' => 'bail|array|max:'.config('const.maximum_attachments_for_care_plan_comment'),
            'removed_attachments.*' => 'bail|required|integer|max:'.config('const.maximum_integer_value'),
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
