<?php

namespace App\Logging;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

class DailyRotatingLogFactory
{
    /**
     * Create a custom Monolog instance.
     */
    public function __invoke(array $config): Logger
    {
        $logger = new Logger('laravel');

        $dir = storage_path('logs');
        $dateFile = $dir . '/' . date('Y-m-d') . '.log';
        $level = $config['level'] ?? Logger::DEBUG;

        $handler = new StreamHandler($dateFile, $level);
        
        $formatter = new LineFormatter(
            "[%datetime%].%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s',
            true,
            true
        );
        
        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);

        return $logger;
    }
}
