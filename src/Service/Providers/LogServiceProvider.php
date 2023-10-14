<?php
declare(strict_types=1);
namespace Zodream\Service\Providers;


use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Zodream\Infrastructure\Support\ServiceProvider;

class LogServiceProvider extends ServiceProvider {


    public function register(): void {
        $this->app->singleton(LoggerInterface::class, function () {
            $args = config('app.log', [
                'name' => 'ZoDream',
                'level' => 'debug',
                'file' => sprintf('data/log/%s.log', date('Y-m-d'))
            ]);

            $log = new Logger($args['name']);
            $log->pushHandler(new StreamHandler((string)app_path($args['file']),
                $args['level']
            ));;
            return $log;
        });
        $this->app->alias(LoggerInterface::class, 'log');
    }
}