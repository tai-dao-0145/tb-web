<?php

namespace App\Helpers;

use App\Enums\Log\SystemLogLevelEnum;
use App\Jobs\SendErrorMailJob;
use App\Models\SystemLog;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Class LogHelperService
 */
class LogHelperService
{
    protected static array $skipClasses = ['Illuminate', 'LogHelperService'];

    /**
     * debug
     *
     * @param string $message : message
     * @param array  $context : context
     * @return void
     */
    public function debug(string $message, array $context = []): void
    {
        $this->getLogChannel()->debug($message, $context);
    }

    /**
     * info
     *
     * @param string $message : message
     * @param array  $context : context
     * @return void
     */
    public function info(string $message, array $context = []): void
    {
        $this->getLogChannel()->info($message, $context);
    }

    /**
     * notice
     *
     * @param string $message : message
     * @param array  $context : context
     * @return void
     */
    public function notice(string $message, array $context = []): void
    {
        $this->getLogChannel()->notice($message, $context);
    }

    /**
     * warning
     *
     * @param string $message : message
     * @param array  $context : context
     * @return void
     */
    public function warning(string $message, array $context = []): void
    {
        $this->getLogChannel()->warning($message, $context);
    }

    /**
     * error
     *
     * @param string $message : message
     * @param array  $context : context
     * @return void
     */
    public function error(string $message, array $context = []): void
    {
        $this->getLogChannel()->error($message, $context);
    }

    /**
     * critical
     *
     * @param string $message : message
     * @param array  $context : context
     * @return void
     */
    public function critical(string $message, array $context = []): void
    {
        $this->getLogChannel()->critical($message, $context);
    }

    /**
     * alert
     *
     * @param string $message : message
     * @param array  $context : context
     * @return void
     */
    public function alert(string $message, array $context = []): void
    {
        $this->getLogChannel()->alert($message, $context);
    }

    /**
     * get log channel
     *
     * @return LoggerInterface
     */
    private function getLogChannel(): LoggerInterface
    {
        $class = $this->getPathFromBackTrace();
        if (str_contains($class, 'Console')) {
            return Log::channel('batch');
        } else {
            return Log::channel(config('logging.custom_channel'));
        }
    }

    /**
     * get path from backtrace
     *
     * @return string
     */
    private function getPathFromBackTrace(): string
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 13);
        for ($i = 0; $i < count($backtrace); $i++) {
            if (isset($backtrace[$i]['class']) && !self::isClassToBeIgnored($backtrace[$i]['class'])) {
                return $backtrace[$i]['class'];
            } elseif (isset($backtrace[$i]['file']) && !self::isClassToBeIgnored($backtrace[$i]['file'])) {
                return $backtrace[$i]['file'];
            }
        }

        return '';
    }

    /**
     * @param string $classOrFile : class file
     * @return bool
     */
    public static function isClassToBeIgnored(string $classOrFile): bool
    {
        foreach (self::$skipClasses as $skipClass) {
            if (str_contains($classOrFile, $skipClass)) {
                return true;
            }
        }

        return false;
    }
}
