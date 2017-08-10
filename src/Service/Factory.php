<?php
namespace Zodream\Service;
/**
 * FACTORY!
 *      EVERYWHERE CAN USE,AND NOT CREATE, AND ALL IS SAME,
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/6/24
 * Time: 22:57
 */
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Zodream\Domain\Access\Auth;
use Zodream\Domain\Debug\Timer;
use Zodream\Domain\Model\UserModel;
use Zodream\Domain\View\ViewFactory;
use Zodream\Infrastructure\Caching\Cache;
use Zodream\Infrastructure\Caching\FileCache;
use Zodream\Disk\Directory;
use Zodream\Infrastructure\Exceptions\Handler;
use Zodream\Infrastructure\Http\Request;
use Zodream\Infrastructure\Http\Input\Header;
use Zodream\Infrastructure\Http\Response;
use Zodream\Infrastructure\I18n\I18n;
use Zodream\Infrastructure\I18n\PhpSource;
use Zodream\Infrastructure\Interfaces\ExceptionHandler;
use Zodream\Infrastructure\Session\Session;
use Zodream\Service\Routing\Router;

class Factory {
    
    private static $_instance = [];

    /**
     * GET A INSTANCE BY KEY
     *      IF HAD RETURN HAD, IF NOT CREATE FROM CONFIG OR DEFAULT
     * @param string $key CONFIG'S KEY
     * @param string $default
     * @return object
     * @throws \Exception
     */
    public static function getInstance($key, $default = null) {
        if (!array_key_exists($key, static::$_instance)) {
            $class = Config::getInstance()->get($key, $default);
            if (is_array($class)) {
                $class = $class['driver'] ?: current($class);
            }
            if (!class_exists($class)) {
                throw new \InvalidArgumentException($class.'CLASS IS NOT EXCITE!');
            }
            static::$_instance[$key] = new $class;
        }
        return static::$_instance[$key];
    }

    /**
     * DO YOU NEED A SESSION , HERE!
     * @return Session
     */
    public static function session() {
        return self::getInstance('session', Session::class);
    }

    /**
     * DO YO WANT TO CACHE MODEL? HERE!
     * @return Cache
     */
    public static function cache() {
        return self::getInstance('cache', FileCache::class);
    }

    /**
     * DO YOU WANT TO SHOW LOCAL LANGUAGE? HERE!
     * @return I18n
     */
    public static function i18n() {
        return self::getInstance('i18n', PhpSource::class);
    }

    /**
     * O! IF YOU NEED ROUTE, HERE. 
     *          IT GO TO DO SOME THING LIKE GO TO CONTROLLER
     * @return Router
     */
    public static function router() {
        return self::getInstance('router', Router::class);
    }

    /**
     * I WANT TO SEND HEADERS WHEN REQUEST FINISH! 
     *      BUT NOW IT'S NOT FINISH!
     *          WOW! PLEASE WAIT A LITTLE TIME.
     * @return Header
     */
    public static function header() {
        return static::response()->header;
    }

    /**
     * @return Response
     */
    public static function response() {
        return self::getInstance('response', Response::class);
    }

    /**
     * GET USER BY SESSION
     * @return UserModel
     */
    public static function user() {
        return Auth::user();
    }

    /**
     * IT IS MAKE VIEW OR HTML FROM ANT FILES,
     * @return ViewFactory
     */
    public static function view() {
        return self::getInstance('viewFactory', ViewFactory::class);
    }

    /**
     * TIMER , LOG ALL TIME
     * @return Timer
     */
    public static function timer() {
        return self::getInstance('timer', Timer::class);
    }

    /**
     * @return ExceptionHandler
     */
    public static function handler() {
        return self::getInstance('exception', Handler::class);
    }

    /**
     * @return Directory
     */
    public static function root() {
        if (!array_key_exists('root', static::$_instance)) {
            static::$_instance['root'] = new Directory(defined('APP_DIR') ? APP_DIR : Request::server('DOCUMENT_ROOT'));
        }
        return static::$_instance['root'];
    }

    /**
     * @return LoggerInterface
     */
    public static function log() {
        if (!array_key_exists('log', static::$_instance)) {
            $args = Config::log();
            $log = new Logger($args['name']);
            $log->pushHandler(new StreamHandler((string)static::root()
                ->childFile($args['file']),
                $args['level']
                ));
            static::$_instance['log'] = $log;
        }
        return static::$_instance['log'];
    }
}