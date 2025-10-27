<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

abstract class BaseEnum extends Enum
{
    /**
     * Get the array of enum descriptions.
     *
     * @return array
     */
    abstract protected static function getDescriptionArray(): array;

    /**
     * Get the description for a specific enum value.
     *
     * @param mixed $key
     * @return string
     */
    public static function getDescription(mixed $key): string
    {
        return static::getDescriptionArray()[$key] ?? (string)$key;
    }

    /**
     * Get an array of all enum descriptions.
     *
     * @return array
     */
    public static function getDescriptions(): array
    {
        return array_values(static::getDescriptionArray());
    }

    /**
     * Get a string representation of the enum values for use in validation rules.
     *
     * @return string
     */
    public static function getRuleIn(): string
    {
        return implode(',', static::getValues());
    }
}
