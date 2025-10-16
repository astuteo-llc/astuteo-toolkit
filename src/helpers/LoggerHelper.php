<?php
namespace astuteo\astuteotoolkit\helpers;

use Craft;
use craft\base\Component;

/**
 * LoggerHelper
 *
 * Simple logging utility for the Astuteo Toolkit plugin.
 * Provides static methods for logging at different severity levels.
 *
 * @package astuteo\astuteotoolkit\helpers
 * @since 3.3.0
 */
class LoggerHelper extends Component
{
    /**
     * Log an informational message
     *
     * @param string $message The message to log
     * @param string|null $category Optional category for the log message
     * @return void
     */
    public static function info(string $message, ?string $category = null): void
    {
        $category = $category ?? 'astuteo-toolkit';
        Craft::info($message, $category);
    }

    /**
     * Log a warning message
     *
     * @param string $message The message to log
     * @param string|null $category Optional category for the log message
     * @return void
     */
    public static function warning(string $message, ?string $category = null): void
    {
        $category = $category ?? 'astuteo-toolkit';
        Craft::warning($message, $category);
    }

    /**
     * Log an error message
     *
     * @param string $message The message to log
     * @param string|null $category Optional category for the log message
     * @return void
     */
    public static function error(string $message, ?string $category = null): void
    {
        $category = $category ?? 'astuteo-toolkit';
        Craft::error($message, $category);
    }

    /**
     * Log a trace message (for development)
     *
     * @param string $message The message to log
     * @param string|null $category Optional category for the log message
     * @return void
     */
    public static function trace(string $message, ?string $category = null): void
    {
        $category = $category ?? 'astuteo-toolkit';
        Craft::trace($message, $category);
    }
}
