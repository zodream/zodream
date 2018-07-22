<?php
namespace Zodream\Domain\Debug;


use Zodream\Infrastructure\Http\Request;

class Log {

    const COLOR_DEFAULT = "\e[0m";

    const COLOR_BLACK = "\e[30m\e[1m";
    const COLOR_ORANGE = "\e[38;5;208m";
    const COLOR_BLUE = "\e[34m";
    const COLOR_GREEN = "\e[92m";

    const COLOR_YELLOW = "\e[93m";

    const COLOR_RED = "\e[91m";

    const COLOR_WHITE = "\e[97m";

    public static function error($message) {
        static::info($message, self::COLOR_RED);
    }

    public static function warning($message) {
        static::info($message, self::COLOR_ORANGE);
    }

    public static function notice($message) {
        static::info($message, self::COLOR_WHITE);
    }

    public static function info($message, $color = null) {
        if (!app('request')->isCli()) {
            return;
        }
        if (empty($color)) {
            echo $message,PHP_EOL;
            return;
        }
        echo $color,$message,self::COLOR_DEFAULT,PHP_EOL;
    }
}