<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class AppEnum extends Enum
{
    public const ENV_TESTING = 'testing';
    public const ENV_LOCAL = 'local';
    public const ENV_DEV = 'development';
    public const ENV_STAGING = 'staging';
    public const ENV_PRODUCTION = 'production';
    public const DATETIME_FORMAT = 'Y-m-d H:i:s';
    public const DATE_FORMAT = 'Y-m-d';
    public const TIME_FORMAT = 'H:i:s';
    public const TIME_24H_FORMAT = 'H:i';
}
