<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\BaseEnum;

/**
 *  Usage:
 *   - Get value int
 *       $property = TypeEnum::SEAT_STATUS_ACTIVE; // Output: 0
 *   - Get label
 *       TypeEnum::getDescription(TypeEnum::SEAT_STATUS_ACTIVE); // Output: '10'
 *   - Get all values
 *       TypeEnum::getValues(); // Output: [0]
 *   - Get all labels
 *       TypeEnum::getDescriptions(); // Output: ['0']
 *   - Validation rules
 *       'type' => 'nullable|numeric|in:' . TypeEnum::getRuleIn(), // Example: in:0,10
 */
final class ExampleEnum extends BaseEnum
{
    public const DRAFT = 0;
    public const PUBLISHED = 1;
    public const ARCHIVED = 9;

    protected static function getDescriptionArray(): array
    {
        return [
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::ARCHIVED => 'Archived',
        ];
    }
}
