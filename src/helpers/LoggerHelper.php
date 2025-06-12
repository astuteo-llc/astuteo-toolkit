<?php

namespace astuteo\astuteotoolkit\helpers;

use Craft;
use Psr\Log\LogLevel;

class LoggerHelper
{
    /**
     * Logs a message to our custom log target.
     */
    public static function log($level, $message)
    {
        $message = "[AstuteoToolkit] " . $message;
        Craft::getLogger()->log($message, $level, 'astuteo-toolkit');
    }

    /**
     * Logs an informational message to our custom log target only in dev mode.
     */
    public static function info($message)
    {
        if (Craft::$app->config->general->devMode) {
            self::log(LogLevel::INFO, $message);
        }
    }

    /**
     * Logs an error message to our custom log target.
     */
    public static function error($message)
    {
        self::log(LogLevel::ERROR, $message);
    }

    /**
     * Logs a warning message to our custom log target.
     */
    public static function warning($message)
    {
        self::log(LogLevel::WARNING, $message);
    }
}