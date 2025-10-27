<?php

namespace App\Exceptions;

use Exception;
class CustomizeException extends Exception

{
    protected array $data;

    public function __construct(string $message, int $code = 0, array $data = [])
    {
        parent::__construct($message, $code);
        $this->data = $data;
    }

    /**
     * Get the data associated with the exception
     *
     * @return array The data associated with the exception
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }
}
