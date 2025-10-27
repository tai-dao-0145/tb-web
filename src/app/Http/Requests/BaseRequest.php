<?php

namespace App\Http\Requests;

use Exception;
use Illuminate\Foundation\Http\FormRequest;

abstract class BaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get data to be validated from the request.
     *
     * @return array
     */
    public function validationData(): array
    {
        return array_merge($this->all(), $this->query());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     * @throws Exception
     */
    public function rules(): array
    {
        return match ($this->getMethod()) {
            'GET' => $this->rulesGet(),
            'POST' => $this->rulesPost(),
            'PUT' => $this->rulesPut(),
            'PATCH' => $this->rulesPatch(),
            'DELETE' => $this->rulesDelete(),
            default => throw new Exception('Not define'),
        };
    }

    /**
     * Message validation
     *
     * @return array
     */
    public function messages(): array
    {
        $default_message = [];
        $messages = $this->getMessages();

        return array_merge($default_message, $messages);
    }

    /**
     * rulesGet
     * handle rule method get
     *
     * @return array
     */
    public function rulesGet(): array
    {
        return [];
    }

    /**
     * rulesPost
     * handle rule method post
     *
     * @return array
     */
    public function rulesPost(): array
    {
        return [];
    }

    /**
     * rulesPut
     * handle rule method put
     *
     * @return array
     */
    public function rulesPut(): array
    {
        return [];
    }

    /**
     * rulesPatch
     * handle rule method patch
     *
     * @return array
     */
    public function rulesPatch(): array
    {
        return [];
    }

    /**
     * rulesPut
     * handle rule method put
     *
     * @return array
     */
    public function rulesDelete(): array
    {
        return [];
    }

    /**
     * Custom message for rule
     *
     * @return array
     */
    abstract public function getMessages(): array;
}
